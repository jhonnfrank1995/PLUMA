import { render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { Aplicacion, type DatosPlumaPanel } from './Aplicacion';
import type { DatosPortada } from './PantallaPortada';
import type { DatosSalud } from './PantallaSalud';

function saludDeEjemplo(): DatosSalud {
    return {
        versionPhp: '8.2.31',
        versionWordPress: '6.7.1',
        versionBaseDatos: '8.0.36',
        versionEsquemaPlugin: '0.7.0',
        cronRealConfigurado: true,
        esMultisitio: false,
        textos: {
            titulo: 'Sala de Máquinas — Salud del sistema',
            etiquetaPhp: 'PHP',
            etiquetaWordPress: 'WordPress',
            etiquetaBaseDatos: 'Base de datos',
            etiquetaEsquema: 'Esquema PLUMA',
            etiquetaCron: 'Cron real',
            cronOk: 'Configurado',
            cronAdvertencia: 'WP-Cron activo',
            etiquetaMultisitio: 'Multisitio',
            multisitioSi: 'Sí',
            multisitioNo: 'No',
        },
    };
}

function datosPanelDeEjemplo(): DatosPlumaPanel {
    return {
        restUrl: 'https://ejemplo.test/wp-json/',
        nonce: 'nonce-de-prueba',
        salud: saludDeEjemplo(),
        textosPortada: {
            titulo: 'Portada',
            navPortada: 'Portada',
            navSalud: 'Sala de Máquinas',
            cargando: 'Cargando…',
            errorCarga: 'No se pudo cargar la Portada.',
            modo: { piloto: 'Piloto', copiloto: 'Copiloto', autonomo: 'Autónomo' },
            cuota: {
                titulo: 'Cuota de hoy',
                publicadas: 'publicadas',
                programadas: 'programadas',
                objetivo: 'objetivo',
                proximaPublicacion: 'Próxima publicación',
                sinProximo: 'sin ranuras programadas pendientes',
                deficit: 'Déficit de cuota',
            },
            salud: {
                titulo: 'Salud del motor',
                ultimaEjecucion: 'Última ejecución',
                nunca: 'el motor no se ha ejecutado todavía',
                gastoHoy: 'Gasto de hoy',
                deLimite: 'de',
                errores: 'con errores',
            },
            pipeline: { titulo: 'Piezas en el pipeline', estados: {} },
            alertas: {
                titulo: 'Alertas',
                retenidas: 'Retenidas esperando decisión',
                fallidas: 'Fallidas',
                sinRetenidas: 'ninguna pieza retenida',
                sinFallidas: 'ninguna pieza fallida',
            },
            tendencias: { titulo: 'Tendencias calientes ahora', vacio: 'todavía no se ha detectado ninguna tendencia' },
        },
        textosTendencias: {
            titulo: 'Sala de Tendencias',
            cargando: 'Cargando…',
            errorCarga: 'No se pudo cargar la Sala de Tendencias.',
            errorAccion: 'La acción no se pudo completar.',
            vacio: 'todavía no se ha detectado ninguna tendencia',
            velocidad: 'Velocidad',
            afinidad: 'Afinidad',
            total: 'Puntuación de Oportunidad',
            desgloseParcial: 'Desglose sobre velocidad y afinidad.',
            quienCubre: 'Quién la está cubriendo ya',
            nadieCubre: 'sin cobertura detectada en las señales',
            estadoVigilada: 'En vigilancia',
            cubrirAhora: 'Cubrir ahora',
            ignorar: 'Ignorar',
            vigilar: 'Vigilar',
        },
        textosMesaEditorial: {
            titulo: 'Mesa Editorial',
            cargando: 'Cargando…',
            errorCarga: 'No se pudo cargar la Mesa Editorial.',
            errorAccion: 'La acción no se pudo completar.',
            columnaVacia: 'sin piezas en este estado',
            sinPeriodista: 'sin periodista asignado',
            sinTesis: 'sin tesis todavía',
            cerrar: 'Cerrar',
            expediente: 'Expediente',
            sinExpediente: 'sin expediente todavía',
            nivelVerificado: 'Verificado',
            nivelAtribuido: 'Atribuido',
            nivelDisputado: 'Disputado',
            ficha: 'Ficha de Decisión Editorial',
            sinFicha: 'sin ficha de decisión editorial todavía',
            tesisElegida: 'Tesis elegida',
            tonoDominante: 'Tono dominante',
            tonoApoyo: 'Tono de apoyo',
            compuertas: 'Compuertas',
            sinCompuertas: 'sin evaluación de compuertas todavía',
            calidad: 'Calidad',
            riesgo: 'Riesgo',
            originalidad: 'Originalidad',
            motivos: 'Motivos',
            borradores: 'Borradores',
            sinBorradores: 'sin borradores todavía',
            cicloAnterior: 'Ciclo anterior',
            cicloActual: 'Ciclo',
            editadoManualmente: 'editado manualmente por un editor',
            aprobadoPorCorrector: 'aprobado por el Corrector Interno',
            editar: 'Editar',
            guardarEdicion: 'Guardar edición',
            cancelar: 'Cancelar',
            contenidoVacio: 'El contenido no puede estar vacío.',
            reasignar: 'Periodista asignado',
            reasignarBoton: 'Reasignar',
            aprobar: 'Forzar aprobación',
            descartar: 'Descartar',
            confirmarDescartar: '¿Descartar esta Pieza?',
        },
    };
}

function portadaDeEjemplo(): DatosPortada {
    return {
        modoOperacion: 'autonomo',
        cuota: { objetivo: 6, minima: 3, maxima: 8, publicadasHoy: 4, programadasHoy: 0, proximaPublicacion: null, deficit: false },
        salud: { ultimaEjecucion: null, gastoHoyUsd: 0.5, limiteDiarioUsd: 5 },
        piezasPorEstado: {},
        alertas: { retenidas: [], fallidas: [] },
        tendenciasCalientes: [],
    };
}

describe('Aplicacion', () => {
    beforeEach(() => {
        window.location.hash = '';
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        window.location.hash = '';
    });

    it('pide la Portada al montar, enviando el nonce de REST, y la muestra', async () => {
        const fetchSimulado = vi.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve(portadaDeEjemplo()),
        });
        vi.stubGlobal('fetch', fetchSimulado);

        render(<Aplicacion datos={datosPanelDeEjemplo()} />);

        await waitFor(() => expect(screen.getByText('Autónomo')).toBeInTheDocument());

        expect(fetchSimulado).toHaveBeenCalledWith(
            'https://ejemplo.test/wp-json/pluma/v1/panel/portada',
            expect.objectContaining({ headers: { 'X-WP-Nonce': 'nonce-de-prueba' } })
        );
    });

    it('muestra el error de carga cuando la petición REST falla', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, json: () => Promise.resolve({}) }));

        render(<Aplicacion datos={datosPanelDeEjemplo()} />);

        await waitFor(() => expect(screen.getByRole('alert')).toHaveTextContent('No se pudo cargar la Portada.'));
    });

    it('navega a la Sala de Máquinas cuando el hash cambia, sin perder la barra de estado', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: () => Promise.resolve(portadaDeEjemplo()) }));

        render(<Aplicacion datos={datosPanelDeEjemplo()} />);

        await waitFor(() => expect(screen.getByText('Autónomo')).toBeInTheDocument());

        window.location.hash = '#/salud';
        window.dispatchEvent(new HashChangeEvent('hashchange'));

        expect(await screen.findByText('Sala de Máquinas — Salud del sistema')).toBeInTheDocument();
        // La barra de estado persiste al navegar (Libro Cap. 10.1).
        expect(screen.getByText('Autónomo')).toBeInTheDocument();
    });
});
