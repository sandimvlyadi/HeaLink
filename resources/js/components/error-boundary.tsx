import { Component, type ErrorInfo, type ReactNode } from 'react';

interface Props {
    children: ReactNode;
    fallback?: ReactNode;
}

interface State {
    hasError: boolean;
    error: Error | null;
}

/**
 * React error boundary that catches unhandled errors in the component tree.
 * Renders a fallback UI when an error is caught.
 */
export class ErrorBoundary extends Component<Props, State> {
    constructor(props: Props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, info: ErrorInfo): void {
        console.error(
            '[ErrorBoundary] Uncaught error:',
            error,
            info.componentStack,
        );
    }

    render(): ReactNode {
        if (this.state.hasError) {
            if (this.props.fallback) {
                return this.props.fallback;
            }

            return (
                <div className="flex min-h-[200px] flex-col items-center justify-center gap-3 rounded-lg border border-red-200 bg-red-50 p-6 text-center dark:border-red-800 dark:bg-red-950/20">
                    <p className="text-sm font-medium text-red-700 dark:text-red-400">
                        Terjadi kesalahan saat memuat komponen ini.
                    </p>
                    <p className="text-xs text-red-500 dark:text-red-500">
                        {this.state.error?.message ?? 'Unknown error'}
                    </p>
                    <button
                        className="mt-2 rounded-md border border-red-300 px-3 py-1 text-xs text-red-700 hover:bg-red-100 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/30"
                        onClick={() =>
                            this.setState({ hasError: false, error: null })
                        }
                    >
                        Coba lagi
                    </button>
                </div>
            );
        }

        return this.props.children;
    }
}
