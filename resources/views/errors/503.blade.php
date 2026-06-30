<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Service Unavailable</title>
        <style>
            :root {
                color-scheme: light;
                --background: #ffffff;
                --foreground: #0f172a;
                --card: #ffffff;
                --muted: #f8fafc;
                --muted-foreground: #64748b;
                --border: #e2e8f0;
                --primary: #18181b;
                --primary-foreground: #ffffff;
                --amber-bg: #fffbeb;
                --amber-border: #fde68a;
                --amber-text: #92400e;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    color-scheme: dark;
                    --background: #09090b;
                    --foreground: #fafafa;
                    --card: #111113;
                    --muted: #18181b;
                    --muted-foreground: #a1a1aa;
                    --border: #27272a;
                    --primary: #fafafa;
                    --primary-foreground: #18181b;
                    --amber-bg: rgba(120, 53, 15, 0.28);
                    --amber-border: rgba(146, 64, 14, 0.6);
                    --amber-text: #fcd34d;
                }
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                background: var(--background);
                color: var(--foreground);
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                font-size: 13px;
            }

            main {
                display: flex;
                min-height: 100vh;
                align-items: center;
                justify-content: center;
                padding: 2rem 1rem;
            }

            .card {
                width: min(100%, 36rem);
                overflow: hidden;
                border: 1px solid var(--border);
                border-radius: 0.5rem;
                background: var(--card);
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            }

            .header,
            .footer {
                padding: 1rem 1.25rem;
            }

            .header {
                border-bottom: 1px solid var(--border);
            }

            .header-row {
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .icon {
                display: inline-flex;
                width: 2.25rem;
                height: 2.25rem;
                flex: 0 0 auto;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--amber-border);
                border-radius: 0.375rem;
                background: var(--amber-bg);
                color: var(--amber-text);
            }

            .eyebrow {
                margin: 0;
                color: var(--muted-foreground);
                font-size: 0.6875rem;
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            h1 {
                margin: 0.25rem 0 0;
                font-size: 1.125rem;
                line-height: 1.35;
                font-weight: 600;
            }

            .body {
                display: grid;
                gap: 1rem;
                padding: 1rem 1.25rem;
            }

            .message {
                border: 1px solid var(--border);
                border-radius: 0.375rem;
                background: color-mix(in srgb, var(--muted) 70%, transparent);
                padding: 0.75rem 1rem;
            }

            .label {
                margin: 0 0 0.25rem;
                color: var(--muted-foreground);
                font-size: 0.75rem;
                font-weight: 500;
            }

            .message p:last-child,
            .hint {
                margin: 0;
                line-height: 1.6;
            }

            .hint {
                color: var(--muted-foreground);
                font-size: 0.75rem;
            }

            .footer {
                display: flex;
                justify-content: flex-end;
                gap: 0.5rem;
                border-top: 1px solid var(--border);
                background: color-mix(in srgb, var(--muted) 45%, transparent);
            }

            .button {
                display: inline-flex;
                height: 2rem;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--border);
                border-radius: 0.375rem;
                background: var(--background);
                color: var(--foreground);
                padding: 0 0.75rem;
                font-size: 0.8125rem;
                font-weight: 500;
                text-decoration: none;
            }

            .button.primary {
                border-color: var(--primary);
                background: var(--primary);
                color: var(--primary-foreground);
            }

            @media (max-width: 520px) {
                .footer {
                    flex-direction: column-reverse;
                }

                .button {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <main>
            <section class="card" aria-labelledby="service-unavailable-title">
                <div class="header">
                    <div class="header-row">
                        <div class="icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                            </svg>
                        </div>
                        <div>
                            <p class="eyebrow">Service Unavailable</p>
                            <h1 id="service-unavailable-title">System maintenance is in progress</h1>
                        </div>
                    </div>
                </div>

                <div class="body">
                    <div class="message">
                        <p class="label">Reason</p>
                        <p>
                            {{ $exception?->getMessage() ?: 'The system is currently unavailable while maintenance work is being completed. Please try again shortly.' }}
                        </p>
                    </div>

                    <p class="hint">
                        The page will become available again once maintenance is complete.
                    </p>
                </div>

                <div class="footer">
                    <a class="button" href="{{ url()->current() }}">Retry</a>
                    <a class="button primary" href="{{ url('/') }}">Go Home</a>
                </div>
            </section>
        </main>
    </body>
</html>
