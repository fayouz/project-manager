// @ts-check
const { test, expect } = require('@playwright/test');
const { Client } = require('pg');

async function resetTestDatabase() {
  const client = new Client({
    host: process.env.DB_HOST || 'database',
    port: parseInt(process.env.DB_PORT || '5432', 10),
    user: process.env.DB_USER || 'app',
    password: process.env.DB_PASSWORD || '!ChangeMe!',
    database: process.env.DB_NAME || 'app_test',
  });

  await client.connect();
  await client.query('TRUNCATE TABLE "user" CASCADE');
  await client.end();
}

/**
 * Attend que l'application Nuxt soit complètement hydratée côté client.
 * @param {import('@playwright/test').Page} page
 */
async function waitForNuxtHydration(page) {
  await page.waitForFunction(() => {
    // @ts-ignore
    return !!document.querySelector('#__nuxt')?.__vue_app__;
  }, { timeout: 15000 });
}

test.describe('Application Setup Flow', () => {
  test.beforeEach(async () => {
    // S'assurer que la base de test est vierge (aucun utilisateur) avant chaque test
    await resetTestDatabase();
  });

  test('redirection vers /setup lorsque aucun utilisateur n\'existe, puis installation complète', async ({ page }) => {
    // 1. Accès à la racine : doit rediriger automatiquement vers /setup
    await page.goto('/');
    await expect(page).toHaveURL(/\/setup$/);
    await waitForNuxtHydration(page);

    // 2. Vérification des éléments de la page de setup
    await expect(page.getByText('Configuration initiale')).toBeVisible();
    await expect(page.getByText('Créez votre compte administrateur pour commencer.')).toBeVisible();

    const emailInput = page.getByPlaceholder('admin@example.com');
    const passwordInput = page.locator('input[type="password"]').first();
    const confirmPasswordInput = page.getByPlaceholder('••••••••');
    const submitButton = page.getByRole('button', { name: "Finaliser l'installation" });

    await expect(emailInput).toBeVisible();
    await expect(passwordInput).toBeVisible();
    await expect(confirmPasswordInput).toBeVisible();
    await expect(submitButton).toBeDisabled();

    // 3. Remplissage avec des données invalides / non concordantes
    await emailInput.fill('admin@example.com');
    await passwordInput.fill('Password123!');
    // Le bouton doit rester désactivé tant que la confirmation ne correspond pas
    await expect(submitButton).toBeDisabled();

    await confirmPasswordInput.fill('DifferentPassword123!');
    await expect(submitButton).toBeDisabled();

    // 4. Remplissage avec mot de passe conforme et confirmation identique
    await confirmPasswordInput.fill('Password123!');
    await expect(submitButton).toBeEnabled();

    // 5. Soumission du formulaire d'installation
    await submitButton.click();

    // 6. Doit être redirigé vers /login avec le paramètre installed=true
    await expect(page).toHaveURL(/\/login\?installed=true$/);
    await expect(page.getByText('Installation terminée avec succès ! Vous pouvez maintenant vous connecter.')).toBeVisible();

    // 7. Tentative d'accès à nouveau à /setup : l'application étant installée, redirection vers /login
    await page.goto('/setup');
    await expect(page).toHaveURL(/\/login$/);

    // 8. Connexion avec les identifiants créés lors du setup
    await waitForNuxtHydration(page);
    await page.getByPlaceholder('utilisateur@domaine.com').fill('admin@example.com');
    await page.locator('input[type="password"]').fill('Password123!');
    await page.getByRole('button', { name: 'Se connecter' }).click();

    // 9. Vérification de l'arrivée sur le dashboard sécurisé
    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(page.getByText('Superadmin')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Tableau de bord' })).toBeVisible();
  });
});
