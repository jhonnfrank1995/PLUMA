import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { BloqueLlaveOpenRouter, type TextosLlaveOpenRouter } from './BloqueLlaveOpenRouter';

function textosDeEjemplo(): TextosLlaveOpenRouter {
    return {
        titulo: 'Llave de OpenRouter',
        actual: 'Llave actual',
        campoNueva: 'Nueva llave',
        guardar: 'Guardar llave',
        probar: 'Probar conexión',
        probando: 'Probando…',
        valida: 'La llave es válida.',
        invalida: 'La llave no es válida.',
        cambiar: 'Cambiar llave',
        quitar: 'Quitar llave',
        confirmarQuitar: '¿Quitar la llave?',
    };
}

describe('BloqueLlaveOpenRouter', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('guarda una llave nueva tras probarla y avisa al padre', async () => {
        const alGuardar = vi.fn();
        const fetchSimulado = vi.fn((url: string) => {
            if (url.endsWith('/motor/llave-openrouter/probar')) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ valida: true }) });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
        });
        vi.stubGlobal('fetch', fetchSimulado);

        render(
            <BloqueLlaveOpenRouter
                restUrl="https://ejemplo.test/wp-json/"
                nonce="nonce-x"
                configurada={false}
                ultimosCuatro={null}
                textos={textosDeEjemplo()}
                alGuardar={alGuardar}
                alError={() => {}}
            />
        );

        const campo = screen.getByLabelText('Nueva llave');
        await userEvent.type(campo, 'sk-or-v1-prueba');
        await userEvent.click(screen.getByRole('button', { name: 'Probar conexión' }));

        expect(await screen.findByText('La llave es válida.')).toBeInTheDocument();

        await userEvent.click(screen.getByRole('button', { name: 'Guardar llave' }));

        await waitFor(() =>
            expect(fetchSimulado).toHaveBeenCalledWith(
                'https://ejemplo.test/wp-json/pluma/v1/motor/llave-openrouter',
                expect.objectContaining({
                    method: 'POST',
                    headers: expect.objectContaining({ 'X-WP-Nonce': 'nonce-x' }),
                    body: JSON.stringify({ llave: 'sk-or-v1-prueba' }),
                })
            )
        );
        await waitFor(() => expect(alGuardar).toHaveBeenCalled());
    });

    it('pide confirmación antes de quitar una llave ya configurada', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(() => Promise.resolve({ ok: true, json: () => Promise.resolve({}) }))
        );
        const confirmSimulado = vi.spyOn(window, 'confirm').mockReturnValue(false);

        render(
            <BloqueLlaveOpenRouter
                restUrl="https://ejemplo.test/wp-json/"
                nonce="n"
                configurada
                ultimosCuatro="ab12"
                textos={textosDeEjemplo()}
                alGuardar={() => {}}
                alError={() => {}}
            />
        );

        expect(screen.getByText(/sk-…ab12/)).toBeInTheDocument();

        await userEvent.click(screen.getByRole('button', { name: 'Quitar llave' }));

        expect(confirmSimulado).toHaveBeenCalledWith('¿Quitar la llave?');
        confirmSimulado.mockRestore();
    });

    it('avisa al padre si guardar falla', async () => {
        const alError = vi.fn();
        vi.stubGlobal(
            'fetch',
            vi.fn(() => Promise.resolve({ ok: false, json: () => Promise.resolve({}) }))
        );

        render(
            <BloqueLlaveOpenRouter
                restUrl="https://ejemplo.test/wp-json/"
                nonce="n"
                configurada={false}
                ultimosCuatro={null}
                textos={textosDeEjemplo()}
                alGuardar={() => {}}
                alError={alError}
            />
        );

        await userEvent.type(screen.getByLabelText('Nueva llave'), 'sk-or-v1-prueba');
        await userEvent.click(screen.getByRole('button', { name: 'Guardar llave' }));

        await waitFor(() => expect(alError).toHaveBeenCalled());
    });
});
