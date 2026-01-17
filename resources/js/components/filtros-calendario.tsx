import { useEffect, useState } from 'react';

import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { type TipoEventoCalendario } from '@/types';

interface FiltrosCalendarioProps {
    onFiltrosChange: (tipos: TipoEventoCalendario[]) => void;
}

const todosLosTipos: TipoEventoCalendario[] = ['general', 'formacion', 'retiro_espiritual', 'reunion_equipo', 'cumpleanos'];

const tipoLabels: Record<TipoEventoCalendario, string> = {
    general: 'Eventos Generales',
    formacion: 'Formación',
    retiro_espiritual: 'Retiros Espirituales',
    reunion_equipo: 'Reuniones de Equipo',
    cumpleanos: 'Cumpleaños',
};

const STORAGE_KEY = 'calendario_filtros';

export default function FiltrosCalendario({ onFiltrosChange }: FiltrosCalendarioProps) {
    const [filtros, setFiltros] = useState<TipoEventoCalendario[]>(() => {
        if (typeof window === 'undefined') {
            return todosLosTipos;
        }

        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            try {
                const parsed = JSON.parse(stored) as TipoEventoCalendario[];
                // Validar que todos los tipos guardados sean válidos
                return parsed.filter((tipo) => todosLosTipos.includes(tipo));
            } catch {
                return todosLosTipos;
            }
        }

        return todosLosTipos;
    });

    useEffect(() => {
        onFiltrosChange(filtros);
    }, [filtros, onFiltrosChange]);

    const toggleTipo = (tipo: TipoEventoCalendario) => {
        const nuevosFiltros = filtros.includes(tipo)
            ? filtros.filter((t) => t !== tipo)
            : [...filtros, tipo].sort((a, b) => todosLosTipos.indexOf(a) - todosLosTipos.indexOf(b));

        setFiltros(nuevosFiltros);

        // Persistir en localStorage
        if (typeof window !== 'undefined') {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(nuevosFiltros));
        }
    };

    const seleccionarTodos = () => {
        setFiltros([...todosLosTipos]);
        if (typeof window !== 'undefined') {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(todosLosTipos));
        }
    };

    const deseleccionarTodos = () => {
        setFiltros([]);
        if (typeof window !== 'undefined') {
            localStorage.setItem(STORAGE_KEY, JSON.stringify([]));
        }
    };

    return (
        <div className="rounded-lg border bg-card p-4">
            <div className="mb-3 flex items-center justify-between">
                <Label className="text-sm font-semibold">Filtrar por tipo de evento</Label>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={seleccionarTodos}
                        className="text-xs text-muted-foreground hover:text-foreground underline"
                    >
                        Todos
                    </button>
                    <span className="text-xs text-muted-foreground">|</span>
                    <button
                        type="button"
                        onClick={deseleccionarTodos}
                        className="text-xs text-muted-foreground hover:text-foreground underline"
                    >
                        Ninguno
                    </button>
                </div>
            </div>
            <div className="flex flex-wrap gap-4">
                {todosLosTipos.map((tipo) => (
                    <div key={tipo} className="flex items-center space-x-2">
                        <Checkbox
                            id={`filtro-${tipo}`}
                            checked={filtros.includes(tipo)}
                            onCheckedChange={() => toggleTipo(tipo)}
                        />
                        <Label
                            htmlFor={`filtro-${tipo}`}
                            className="text-sm font-normal cursor-pointer"
                        >
                            {tipoLabels[tipo]}
                        </Label>
                    </div>
                ))}
            </div>
        </div>
    );
}
