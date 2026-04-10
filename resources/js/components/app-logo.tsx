import AppLogoIcon from '@/components/app-logo-icon';
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-6 items-center justify-center rounded-md">
                <AppLogoIcon className="size-6" />
            </div>
            <div className="grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {appName}
                </span>
            </div>
        </>
    );
}
