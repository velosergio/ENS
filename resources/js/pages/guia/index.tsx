import { Head } from '@inertiajs/react';
import { Loader2, MessageSquare, Send } from 'lucide-react';
import { type FormEvent, useEffect, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { chat as guiaChat, index as guiaIndex } from '@/routes/guia';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'GUIA',
        href: guiaIndex().url,
    },
];

interface Mensaje {
    id: string;
    texto: string;
    esUsuario: boolean;
    timestamp: Date;
}

export default function GuiaIndex() {
    const [mensaje, setMensaje] = useState('');
    const [mensajes, setMensajes] = useState<Mensaje[]>([]);
    const [enviando, setEnviando] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const scrollRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        scrollRef.current?.scrollTo({ top: scrollRef.current.scrollHeight, behavior: 'smooth' });
    }, [mensajes, enviando]);

    const enviarMensaje = async (e?: FormEvent) => {
        e?.preventDefault();
        if (!mensaje.trim() || enviando) return;

        const mensajeUsuario: Mensaje = {
            id: Date.now().toString(),
            texto: mensaje.trim(),
            esUsuario: true,
            timestamp: new Date(),
        };

        setMensajes((prev) => [...prev, mensajeUsuario]);
        setMensaje('');
        setEnviando(true);
        setError(null);

        try {
            const response = await fetch(guiaChat().url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    Accept: 'application/json',
                },
                body: JSON.stringify({ mensaje: mensajeUsuario.texto }),
            });

            const data = await response.json();

            if (data.success) {
                const mensajeRespuesta: Mensaje = {
                    id: (Date.now() + 1).toString(),
                    texto: data.respuesta,
                    esUsuario: false,
                    timestamp: new Date(),
                };
                setMensajes((prev) => [...prev, mensajeRespuesta]);
            } else {
                setError(data.error || 'Error al enviar el mensaje');
            }
        } catch {
            setError('Error de conexión. Por favor, intenta nuevamente.');
        } finally {
            setEnviando(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="GUIA" />

            {/* Contenedor tipo ChatGPT: flex columna, input siempre abajo */}
            <div className="flex min-h-0 flex-1 flex-col">
                {/* Zona de mensajes: scroll independiente */}
                <div
                    ref={scrollRef}
                    className="min-h-0 flex-1 overflow-y-auto"
                >
                    <div className="mx-auto max-w-3xl px-4 py-6">
                        {mensajes.length === 0 ? (
                            <div className="flex min-h-[40vh] flex-col items-center justify-center gap-3 text-center">
                                <div className="rounded-full bg-muted p-4">
                                    <MessageSquare className="h-10 w-10 text-muted-foreground" />
                                </div>
                                <div>
                                    <h2 className="text-lg font-medium">GUIA</h2>
                                    <p className="text-sm text-muted-foreground">
                                        Sistema de asistencia para consultas sobre la guía ENS
                                    </p>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Escribe tu pregunta para comenzar
                                </p>
                            </div>
                        ) : (
                            <div className="flex flex-col gap-6">
                                {mensajes.map((msg) => (
                                    <div
                                        key={msg.id}
                                        className={cn(
                                            'flex',
                                            msg.esUsuario ? 'justify-end' : 'justify-start',
                                        )}
                                    >
                                        <div
                                            className={cn(
                                                'max-w-[85%] rounded-2xl px-4 py-3',
                                                msg.esUsuario
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'bg-muted',
                                            )}
                                        >
                                            <p className="whitespace-pre-wrap text-sm leading-relaxed">
                                                {msg.texto}
                                            </p>
                                            <p
                                                className={cn(
                                                    'mt-1.5 text-xs',
                                                    msg.esUsuario
                                                        ? 'text-primary-foreground/70'
                                                        : 'text-muted-foreground',
                                                )}
                                            >
                                                {msg.timestamp.toLocaleTimeString('es-ES', {
                                                    hour: '2-digit',
                                                    minute: '2-digit',
                                                })}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                                {enviando && (
                                    <div className="flex justify-start">
                                        <div className="rounded-2xl bg-muted px-4 py-3">
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                {/* Input fijo en la parte inferior — siempre visible */}
                <div className="shrink-0 border-t border-border bg-background px-4 py-4">
                    <div className="mx-auto max-w-3xl">
                        {error && (
                            <div className="mb-3 rounded-lg border border-destructive bg-destructive/10 px-3 py-2 text-sm text-destructive">
                                {error}
                            </div>
                        )}
                        <form
                            onSubmit={enviarMensaje}
                            className="flex gap-2 rounded-2xl border border-input bg-background py-2 pl-4 pr-2 shadow-sm focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-[3px]"
                        >
                            <textarea
                                value={mensaje}
                                onChange={(e) => setMensaje(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && !e.shiftKey) {
                                        e.preventDefault();
                                        enviarMensaje();
                                    }
                                }}
                                placeholder="Escribe tu pregunta..."
                                disabled={enviando}
                                maxLength={1000}
                                rows={1}
                                className="border-input placeholder:text-muted-foreground min-h-[24px] max-h-[200px] flex-1 resize-none bg-transparent py-2 text-base outline-none disabled:opacity-50 md:text-sm"
                            />
                            <Button
                                type="submit"
                                size="icon"
                                disabled={enviando || !mensaje.trim()}
                                className="shrink-0 self-end rounded-xl"
                            >
                                {enviando ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <Send className="h-4 w-4" />
                                )}
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
