<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\PublicTaxonomyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome')->name('home');

Route::inertia('pricing', 'pricing')->name('pricing');
Route::inertia('features', 'features')->name('features');
Route::inertia('about', 'about')->name('about');
Route::inertia('contact', 'contact')->name('contact');

Route::get('blogs', [BlogController::class, 'publicIndex'])->name('blogs.index');
Route::get('blogs/{slug}', [BlogController::class, 'publicShow'])->name('blogs.show');

Route::get('categories', [PublicTaxonomyController::class, 'categoriesIndex'])->name('categories.index');
Route::get('categories/{slug}', [PublicTaxonomyController::class, 'categoriesShow'])->name('categories.show');

Route::get('tags', [PublicTaxonomyController::class, 'tagsIndex'])->name('tags.index');
Route::get('tags/{slug}', [PublicTaxonomyController::class, 'tagsShow'])->name('tags.show');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
         Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('search-api', [SearchController::class, 'search'])->name('search.api');
        Route::get('activity', ActivityController::class)->name('activity.index');
        Route::get('files', [FileController::class, 'index'])->name('files.index');
        Route::post('files', [FileController::class, 'store'])->name('files.store');
        Route::delete('files/{file}', [FileController::class, 'destroy'])->name('files.destroy');



          // ── Blog management ─────────────────────────────────────────────
        Route::prefix('manage/blogs')->name('manage.blogs.')->group(function () {
            Route::get('/', [BlogController::class, 'index'])->name('index');
            Route::get('/create', [BlogController::class, 'create'])->name('create');
            Route::post('/', [BlogController::class, 'store'])->name('store');
            Route::get('/{blog:slug}', [BlogController::class, 'show'])->name('show');
            Route::get('/{blog:slug}/edit', [BlogController::class, 'edit'])->name('edit');
            // POST with _method=PUT so multipart forms work
            Route::put('/{blog:slug}', [BlogController::class, 'update'])->name('update');
            Route::delete('/{blog:slug}', [BlogController::class, 'destroy'])->name('destroy');
        });

        // ── Category management (slug-based) ────────────────────────────
        Route::prefix('manage/categories')->name('manage.categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::put('/{category:slug}', [CategoryController::class, 'update'])->name('update');
            Route::delete('/{category:slug}', [CategoryController::class, 'destroy'])->name('destroy');
        });

        // ── Tag management (slug-based) ──────────────────────────────────
        Route::prefix('manage/tags')->name('manage.tags.')->group(function () {
            Route::get('/', [TagController::class, 'index'])->name('index');
            Route::post('/', [TagController::class, 'store'])->name('store');
            Route::put('/{tag:slug}', [TagController::class, 'update'])->name('update');
            Route::delete('/{tag:slug}', [TagController::class, 'destroy'])->name('destroy');
        });
    });

Route::middleware(['auth'])->group(function () {
    $missingInvitation = function (\Illuminate\Http\Request $request) {
        \Inertia\Inertia::flash('toast', ['type' => 'error', 'message' => __('This invitation is no longer valid or has been cancelled.')]);
        
        if ($user = $request->user()) {
            if ($team = $user->currentTeam) {
                return redirect()->route('dashboard', ['current_team' => $team->slug]);
            }
        }
        
        return redirect()->route('home');
    };

    Route::get('invitations/{invitation}', [TeamInvitationController::class, 'show'])
        ->name('invitations.show')
        ->missing($missingInvitation);

    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])
        ->name('invitations.accept')
        ->missing($missingInvitation);

    Route::post('invitations/{invitation}/decline', [TeamInvitationController::class, 'decline'])
        ->name('invitations.decline')
        ->missing($missingInvitation);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('billing/checkout/{plan}', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
    Route::post('billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
    Route::post('billing/swap/{plan}', [BillingController::class, 'swap'])->name('billing.swap');
    Route::get('billing/invoice/{invoice}', [BillingController::class, 'downloadInvoice'])->name('billing.invoice');


    Route::get('api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    Route::get('notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read', [NotificationCenterController::class, 'markAllRead'])->name('notifications.read');

    Route::get('admin', AdminController::class)->name('admin.index');
});

require __DIR__.'/settings.php';
