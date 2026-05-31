import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import MarketingLayout from './layouts/marketing-layout';

function ToastListener() {
    useFlashToast();
    
    return null;
}


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
            case name === 'pricing':
            case name === 'features':
            case name === 'about':
            case name === 'contact':
            case name.startsWith('blogs/'):
            case name.startsWith('categories/'):
            case name.startsWith('tags/'):
                return MarketingLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
            case name.startsWith('teams/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
                <ToastListener />
            </TooltipProvider>
        );
    },
    progress: {
       color: 'oklch(0.55 0.22 275)',
    },
});

// This will set light / dark mode on load...
initializeTheme();
