import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { PantallaComentarios, type BorradorRespuesta, type TextosComentarios } from './PantallaComentarios';

function textosDeEjemplo(): TextosComentarios {
    return {
        titulo: 'Sala de Comentarios',
        cargando: 'Cargando…',
        errorCarga: 'No se pudo cargar la Sala de Comentarios.',
        errorAccion: 'La acción no se pudo completar.',
        vacio: 'no hay borradores de respuesta pendientes de aprobación',
        borrador: 'Borrador de respuesta',
        aprobar: 'Aprobar',
        descartar: 'Descartar',
    };
}

function borradorDeEjemplo(sobrescribir: Partial<BorradorRespuesta> = {}): BorradorRespuesta {
    return {
        id: 5,
        piezaId: 30,
        comentarioId: 999,
        periodistaId: 7,
        borrador: 'Gracias por comentar, aquí va más contexto.',
        creadaEn: '2026-07-23T08:00:00+00:00',
        ...sobrescribir,
    };
}

function stubFetchConPendientes(pendientes: BorradorRespuesta[]) {
    const fetchSimulado = vi.fn().mockResolvedValue({ ok: true, json: () => Promise.resolve(pendientes) });
    vi.stubGlobal('fetch', fetchSimulado);
    return fetchSimulado;
}

describe('PantallaComentarios', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('muestra el borrador pendiente', async () => {
        stubFetchConPendientes([borradorDeEjemplo()]);

        render(<PantallaComentarios restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        expect(await screen.findByText('Gracias por comentar, aquí va más contexto.')).toBeInTheDocument();
    });

    it('aprueba contra la ruta REST correcta con el nonce y recarga', async () => {
        const fetchSimulado = stubFetchConPendientes([borradorDeEjemplo()]);

        render(<PantallaComentarios restUrl="https://ejemplo.test/wp-json/" nonce="nonce-x" textos={textosDeEjemplo()} />);

        await userEvent.click(await screen.findByRole('button', { name: 'Aprobar' }));

        await waitFor(() =>
            expect(fetchSimulado).toHaveBeenCalledWith(
                'https://ejemplo.test/wp-json/pluma/v1/comentarios/5/aprobar',
                expect.objectContaining({ method: 'POST', headers: { 'X-WP-Nonce': 'nonce-x' } })
            )
        );
        // GET inicial + POST + GET de recarga.
        await waitFor(() => expect(fetchSimulado).toHaveBeenCalledTimes(3));
    });

    it('descarta contra la ruta REST correcta', async () => {
        const fetchSimulado = stubFetchConPendientes([borradorDeEjemplo()]);

        render(<PantallaComentarios restUrl="https://ejemplo.test/wp-json/" nonce="nonce-x" textos={textosDeEjemplo()} />);

        await userEvent.click(await screen.findByRole('button', { name: 'Descartar' }));

        await waitFor(() =>
            expect(fetchSimulado).toHaveBeenCalledWith(
                'https://ejemplo.test/wp-json/pluma/v1/comentarios/5/descartar',
                expect.objectContaining({ method: 'POST', headers: { 'X-WP-Nonce': 'nonce-x' } })
            )
        );
    });

    it('muestra el error de acción si el POST falla', async () => {
        const fetchSimulado = vi
            .fn()
            .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve([borradorDeEjemplo()]) })
            .mockResolvedValueOnce({ ok: false, json: () => Promise.resolve({}) });
        vi.stubGlobal('fetch', fetchSimulado);

        render(<PantallaComentarios restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        await userEvent.click(await screen.findByRole('button', { name: 'Aprobar' }));

        await waitFor(() => expect(screen.getByRole('alert')).toHaveTextContent('La acción no se pudo completar.'));
    });

    it('muestra el mensaje vacío cuando no hay borradores pendientes', async () => {
        stubFetchConPendientes([]);

        render(<PantallaComentarios restUrl="https://ejemplo.test/wp-json/" nonce="n" textos={textosDeEjemplo()} />);

        expect(await screen.findByText('no hay borradores de respuesta pendientes de aprobación')).toBeInTheDocument();
    });
});
