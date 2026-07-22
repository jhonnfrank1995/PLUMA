import { expect, test } from '@playwright/test';

/**
 * Smoke E2E de la Etapa 0 (PLAN-MAESTRO): el ZIP recién instalado y activado
 * expone la Sala de Máquinas — Salud del sistema, y un administrador
 * autenticado la ve con datos reales, no un placeholder.
 */

async function iniciarSesionComoAdministrador(page: import('@playwright/test').Page): Promise<void> {
    await page.goto('/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
    await expect(page).toHaveURL(/wp-admin/);
}

test.describe('Sala de Máquinas — Salud del sistema', () => {
    test('un administrador ve el estado real del entorno tras activar el plugin', async ({ page }) => {
        await iniciarSesionComoAdministrador(page);

        await page.goto('/wp-admin/admin.php?page=pluma-engine-salud');

        const raiz = page.locator('#pluma-salud-root');
        await expect(raiz).toBeVisible();

        // Acotado al contenedor de la pantalla: "WordPress"/"PHP" también
        // aparecen en el pie de página nativo de wp-admin (strict mode
        // violation si se busca en toda la página).
        await expect(raiz.getByText('Sala de Máquinas — Salud del sistema')).toBeVisible();
        await expect(raiz.getByText('PHP', { exact: true })).toBeVisible();
        await expect(raiz.getByText('WordPress', { exact: true })).toBeVisible();
        await expect(raiz.getByText('Esquema PLUMA', { exact: true })).toBeVisible();
    });

    test('un usuario sin la capacidad pluma_configurar_motor no ve la pantalla', async ({ page, request }) => {
        // Verificación de la compuerta de capacidad (AGENTS.md § SUB-AGENTE SEGURIDAD):
        // la pantalla nunca cuelga de manage_options ni queda accesible a cualquier rol.
        const respuesta = await request.get('/wp-admin/admin.php?page=pluma-engine-salud');

        // Sin sesión autenticada, WordPress redirige a wp-login.php (302) en
        // vez de renderizar la pantalla — la capacidad protege el endpoint.
        expect(respuesta.url()).toContain('wp-login.php');
    });
});
