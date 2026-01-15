import { useSidebar } from '@/components/ui/sidebar';

export default function AppLogo() {
    const { state } = useSidebar();
    const isCollapsed = state === 'collapsed';

    return (
        <div className="flex size-full items-center justify-center">
            {isCollapsed ? (
                <img
                    src="/favicon.svg"
                    alt="Equipos de Nuestra Señora"
                    className="size-8"
                />
            ) : (
                <img
                    src="/logo.svg"
                    alt="Equipos de Nuestra Señora"
                    className="h-auto max-h-8 w-auto"
                />
            )}
        </div>
    );
}
