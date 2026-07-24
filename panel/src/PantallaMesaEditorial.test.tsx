import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    PantallaMesaEditorial,
    type DetallePieza,
    type KanbanMesaEditorial,
    type TextosMesaEditorial,
} from './PantallaMesaEditorial';

function textosDeEjemplo(): TextosMesaEditorial {
    return {
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
        actualizacionDe: 'Actualización de la pieza',
    };
}

function kanbanDeEjemplo(): KanbanMesaEditorial {
    const kanban: KanbanMesaEditorial = {
        detectada: [],
        en_investigacion: [],
        investigada: [],
        en_redaccion: [],
        redactada: [],
        optimizada: [],
        en_revision: [],
        aprobada: [],
        programada: [],
        publicada: [],
        retenida: [
            {
                id: 42,
                tendenciaTermino: 'elecciones 2026',
                periodista: { id: 1, nombre: 'Ana Reyes' },
                tesisCorta: 'El sistema electoral enfrenta una prueba de estrés',
                tonoDominante: 'analitico',
                actualizadaEn: '2026-07-23T08:00:00+00:00',
            },
        ],
        descartada: [],
        fallida: [],
    };

    return kanban;
}

function detalleDeEjemplo(sobrescribir: Partial<DetallePieza> = {}): DetallePieza {
    return {
        id: 42,
        estado: 'retenida',
        tendenciaTermino: 'elecciones 2026',
        periodista: { id: 1, nombre: 'Ana Reyes' },
        expediente: {
            tendenciaOrigen: 'elecciones 2026',
            hechos: [{ extracto: 'El padrón creció 12%.', url: 'https://example.com/a', fecha: '2026-07-20T00:00:00+00:00', nivel: 'verificado' }],
        },
        fichaDecisionEditorial: {
            periodistaId: 1,
            periodistaVersionId: 3,
            clasificacion: { tema: 'politica', gravedad: 3, polaridad: 'neutra', novedad: 'desarrollo', potencialConversacional: 4, tipoNoticia: 'analisis' },
            candidatosTesis: [
                { tesis: 'El sistema electoral enfrenta una prueba de estrés', puntuacionOriginalidad: 80, puntuacionCompatibilidadLinea: 70, puntuacionSustento: 90, puntuacionConversacional: 60 },
            ],
            indiceTesisElegida: 0,
            tonoDominante: 'analitico',
            tonoApoyo: 'critico',
            esqueleto: { gancho: 'g', hechosEsencialesConAtribucion: 'h', movimientosArgumentales: ['m1'], contraargumentoReconocido: 'c', remate: 'r' },
            creadaEn: '2026-07-23T07:00:00+00:00',
        },
        resultadoCompuertas: {
            aprobada: false,
            retenida: true,
            motivos: ['riesgo de difamación'],
            modoEfectivo: 'copiloto',
            calidad: { puntuacionTotal: 80, umbral: 70, sustentoAprobado: true, detalle: [] },
            riesgo: {
                implicaTragedia: false,
                implicaMenores: false,
                implicaSalud: false,
                implicaViolencia: false,
                riesgoDifamacion: true,
                detalleDifamacion: 'afirmación sobre persona identificable sin doble fuente',
                hechosDisputadosSinSenalar: false,
                temaRegulado: null,
            },
            originalidad: { solapamientoConFuentes: false, solapamientoConSitioPropio: false, ratioGananciaInformacion: 0.8, umbralGananciaMinima: 0.4 },
        },
        postId: null,
        piezaOriginalId: null,
        creadaEn: '2026-07-23T06:00:00+00:00',
        actualizadaEn: '2026-07-23T08:00:00+00:00',
        borradores: [
            { id: 1, numeroCiclo: 1, contenido: 'Primera versión del texto.\nSegunda línea.', anotaciones: [], aprobadoPorCorrector: false, editadoManualmente: false, creadoEn: '2026-07-23T07:10:00+00:00' },
            { id: 2, numeroCiclo: 2, contenido: 'Primera versión revisada.\nSegunda línea.', anotaciones: [], aprobadoPorCorrector: true, editadoManualmente: false, creadoEn: '2026-07-23T07:30:00+00:00' },
        ],
        periodistasActivos: [
            { id: 1, nombre: 'Ana Reyes' },
            { id: 2, nombre: 'Luis Gómez' },
        ],
        ...sobrescribir,
    };
}

function stubFetch(kanban: KanbanMesaEditorial, detalle: DetallePieza) {
    const fetchSimulado = vi.fn((url: string, opciones?: RequestInit) => {
        if (url.endsWith('/piezas/kanban')) {
            return Promise.resolve({ ok: true, json: () => Promise.resolve(kanban) });
        }
        if (url.includes('/piezas/') && (!opciones || 'POST' !== opciones.method)) {
            return Promise.resolve({ ok: true, json: () => Promise.resolve(detalle) });
        }
        return Promise.resolve({ ok: true, json: () => Promise.resolve({ piezaId: detalle.id }) });
    });
    vi.stubGlobal('fetch', fetchSimulado);
    return fetchSimulado;
}

describe('PantallaMesaEditorial', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('muestra el kanban con la tarjeta en su columna de estado', async () => {
        stubFetch(kanbanDeEjemplo(), detalleDeEjemplo());

        render(<PantallaMesaEditorial restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        expect(await screen.findByText('elecciones 2026')).toBeInTheDocument();
        expect(screen.getByText('Ana Reyes')).toBeInTheDocument();
    });

    it('abre el detalle con expediente, ficha y compuertas al hacer clic en una tarjeta', async () => {
        stubFetch(kanbanDeEjemplo(), detalleDeEjemplo());

        render(<PantallaMesaEditorial restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        await userEvent.click(await screen.findByText('elecciones 2026'));

        const panel = await screen.findByRole('dialog');
        expect(within(panel).getByText('El padrón creció 12%.')).toBeInTheDocument();
        expect(within(panel).getByText('El sistema electoral enfrenta una prueba de estrés')).toBeInTheDocument();
        expect(within(panel).getByText('afirmación sobre persona identificable sin doble fuente')).toBeInTheDocument();
    });

    it('muestra el botón Forzar aprobación solo cuando la Pieza está RETENIDA', async () => {
        stubFetch(kanbanDeEjemplo(), detalleDeEjemplo({ estado: 'optimizada' }));

        render(<PantallaMesaEditorial restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        await userEvent.click(await screen.findByText('elecciones 2026'));
        await screen.findByRole('dialog');

        expect(screen.queryByRole('button', { name: 'Forzar aprobación' })).not.toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Descartar' })).toBeInTheDocument();
    });

    it('no muestra ninguna acción de escritura sobre una Pieza ya publicada', async () => {
        stubFetch(kanbanDeEjemplo(), detalleDeEjemplo({ estado: 'publicada' }));

        render(<PantallaMesaEditorial restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        await userEvent.click(await screen.findByText('elecciones 2026'));
        await screen.findByRole('dialog');

        expect(screen.queryByRole('button', { name: 'Forzar aprobación' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Descartar' })).not.toBeInTheDocument();
    });

    it('calcula el diff entre el ciclo elegido y el anterior', async () => {
        stubFetch(kanbanDeEjemplo(), detalleDeEjemplo());

        render(<PantallaMesaEditorial restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        await userEvent.click(await screen.findByText('elecciones 2026'));
        await screen.findByRole('dialog');

        expect(screen.getByText('Ciclo anterior: #1')).toBeInTheDocument();
        expect(screen.getByText('Primera versión revisada.')).toBeInTheDocument();
        expect(screen.getByText('Primera versión del texto.')).toBeInTheDocument();
    });

    it('reasigna el periodista con el nonce correcto', async () => {
        const fetchSimulado = stubFetch(kanbanDeEjemplo(), detalleDeEjemplo());

        render(<PantallaMesaEditorial restUrl="https://ejemplo.test/wp-json/" nonce="nonce-x" textos={textosDeEjemplo()} />);

        await userEvent.click(await screen.findByText('elecciones 2026'));
        await screen.findByRole('dialog');

        await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Periodista asignado' }), '2');
        await userEvent.click(screen.getByRole('button', { name: 'Reasignar' }));

        await waitFor(() =>
            expect(fetchSimulado).toHaveBeenCalledWith(
                'https://ejemplo.test/wp-json/pluma/v1/piezas/42/reasignar',
                expect.objectContaining({
                    method: 'POST',
                    headers: expect.objectContaining({ 'X-WP-Nonce': 'nonce-x', 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ periodistaId: '2' }),
                })
            )
        );
    });

    it('edita el borrador y envía el nuevo contenido', async () => {
        const fetchSimulado = stubFetch(kanbanDeEjemplo(), detalleDeEjemplo());

        render(<PantallaMesaEditorial restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        await userEvent.click(await screen.findByText('elecciones 2026'));
        await screen.findByRole('dialog');

        await userEvent.click(screen.getByRole('button', { name: 'Editar' }));

        const textarea = screen.getByRole('textbox');
        await userEvent.clear(textarea);
        await userEvent.type(textarea, 'Texto corregido a mano.');
        await userEvent.click(screen.getByRole('button', { name: 'Guardar edición' }));

        await waitFor(() =>
            expect(fetchSimulado).toHaveBeenCalledWith(
                'https://ejemplo.test/wp-json/pluma/v1/piezas/42/editar',
                expect.objectContaining({ method: 'POST', body: JSON.stringify({ contenido: 'Texto corregido a mano.' }) })
            )
        );
    });

    it('pide confirmación antes de descartar', async () => {
        stubFetch(kanbanDeEjemplo(), detalleDeEjemplo());
        const confirmSimulado = vi.spyOn(window, 'confirm').mockReturnValue(false);

        render(<PantallaMesaEditorial restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        await userEvent.click(await screen.findByText('elecciones 2026'));
        await userEvent.click(await screen.findByRole('button', { name: 'Descartar' }));

        expect(confirmSimulado).toHaveBeenCalledWith('¿Descartar esta Pieza?');
        confirmSimulado.mockRestore();
    });
});
