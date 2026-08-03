import { expect, Page, test } from '@playwright/test';

async function login(page: Page) {
  await page.goto('/wp-login.php');
  await page.locator('input[name="log"]').fill('wordpress');
  await page.locator('input[name="pwd"]').fill('wordpress');
  await page.locator('#wp-submit').click();
}

test('administrator can open Bluem settings and persist a change', async ({ page }) => {
  await login(page);

  await page.goto('/wp-admin/admin.php?page=bluem-settings');
  await expect(page.locator('h1')).toContainText('Settings');
  await page.locator('a[data-tab="account"]').click();
  await expect(page.locator('#bluem_woocommerce_settings_senderID')).toBeVisible();
  await expect(page.locator('#bluem_woocommerce_settings_environment')).toBeVisible();
  await page.locator('#bluem_woocommerce_settings_senderID').fill('playwright-acceptance-sender');
  await page.locator('#bluem_woocommerce_settings_environment').selectOption('test');
  await page.locator('input[name="submit"]').click();
  await page.goto('/wp-admin/admin.php?page=bluem-settings');
  await expect(page.locator('#bluem_woocommerce_settings_senderID')).toHaveValue('playwright-acceptance-sender');
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
});

test('administrator can open WooCommerce payment settings', async ({ page }) => {
  await login(page);
  await page.goto('/wp-admin/admin.php?page=wc-settings&tab=checkout');
  await expect(page.locator('body')).toContainText('Bluem iDEAL Acceptance');
  await expect(page.locator('body')).toContainText('Bluem PayPal');
});

test('administrator can inspect the fixture order and Bluem transaction', async ({ page }) => {
  await login(page);

  const orderId = process.env.WP_ACCEPTANCE_FIXTURE_ORDER_ID;
  const requestId = process.env.WP_ACCEPTANCE_FIXTURE_REQUEST_ID;
  expect(orderId).toBeTruthy();
  expect(requestId).toBeTruthy();

  await page.goto(`/wp-admin/admin.php?page=wc-orders&action=edit&id=${orderId}`);
  await expect(page.locator('body')).toContainText('Bluem request(s)');
  await expect(page.locator('body')).toContainText('ACCEPTANCE-TRANSACTION-1');

  await page.goto(`/wp-admin/admin.php?page=bluem-transactions&request_id=${requestId}`);
  await expect(page.locator('body')).toContainText('ACCEPTANCE-TRANSACTION-1');
  await expect(page.locator('body')).toContainText('Bluem acceptance fixture payment');
});
