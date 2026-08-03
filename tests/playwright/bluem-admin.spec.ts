import { expect, Page, test } from '@playwright/test';

async function login(page: Page) {
  await page.goto('/wp-login.php');
  await page.locator('input[name="log"]').fill('wordpress');
  await page.locator('input[name="pwd"]').fill('wordpress');
  await page.locator('#wp-submit').click();
}

test('administrator can inspect the fixture order and Bluem transaction', async ({ page }) => {
  await login(page);

  const orderId = process.env.WP_ACCEPTANCE_FIXTURE_ORDER_ID;
  const requestId = process.env.WP_ACCEPTANCE_FIXTURE_REQUEST_ID;
  expect(orderId).toBeTruthy();
  expect(requestId).toBeTruthy();

  await page.goto(`/wp-admin/admin.php?page=wc-orders&action=edit&id=${orderId}`);
  await expect(page.locator('body')).toContainText('Bluem request(s)');
  await expect(page.locator('body')).toContainText('ACCEPTANCETX1');

  await page.goto(`/wp-admin/admin.php?page=bluem-transactions&request_id=${requestId}`);
  await expect(page.locator('h1')).toContainText('Transaction details');
  await expect(page.locator('body')).toContainText('ACCEPTANCETX1');
});

test('administrator can deactivate and reactivate Bluem', async ({ page }) => {
  await login(page);
  await page.goto('/wp-admin/plugins.php');

  const pluginRow = page.locator('#the-list tr').filter({ hasText: 'Bluem ePayments' });
  await expect(pluginRow).toBeVisible();
  await pluginRow.getByRole('link', { name: 'Deactivate' }).click();
  await expect(pluginRow.getByRole('link', { name: 'Activate' })).toBeVisible();
  await pluginRow.getByRole('link', { name: 'Activate' }).click();
  await expect(pluginRow.getByRole('link', { name: 'Deactivate' })).toBeVisible();

  // Activation resets Bluem's setup guard. Complete the local activation form
  // so the plugin can continue serving normal admin pages.
  await page.goto('/wp-admin/admin.php?page=bluem-activate');
  await page.locator('#company_name').fill('Bluem Acceptance');
  await page.locator('#company_telephone').fill('0200000000');
  await page.locator('#company_email').fill('acceptance@example.com');
  await page.locator('#tech_name').fill('Bluem Acceptance Tester');
  await page.locator('#tech_telephone').fill('0200000001');
  await page.locator('#tech_email').fill('tester@example.com');
  await page.locator('#activateform input[type="submit"]').click();
  await expect(page.locator('body')).toContainText('The plugin has been activated');
});

test('administrator can open WooCommerce payment settings', async ({ page }) => {
  await login(page);
  await page.goto('/wp-admin/admin.php?page=wc-settings&tab=checkout');
  await expect(page.locator('body')).toContainText('Bluem payments via iDEAL');
  await expect(page.locator('body')).toContainText('Bluem payments via PayPal');
});

test('administrator can open Bluem settings and persist a change', async ({ page }) => {
  await login(page);

  await page.goto('/wp-admin/admin.php?page=bluem-settings');
  await expect(page.locator('h1')).toContainText('Settings');
  await page.locator('a[data-tab="account"]').click();
  await expect(page.locator('#bluem_woocommerce_settings_senderID')).toBeVisible();
  await expect(page.locator('#bluem_woocommerce_settings_environment')).toBeVisible();
  await page.locator('#bluem_woocommerce_settings_senderID').fill('S123456');
  await page.locator('#bluem_woocommerce_settings_environment').selectOption('test');
  await page.locator('input[name="submit"]').click();
  await page.goto('/wp-admin/admin.php?page=bluem-settings');
  await expect(page.locator('#bluem_woocommerce_settings_senderID')).toHaveValue('S123456');
});
