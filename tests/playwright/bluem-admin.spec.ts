import { expect, test } from '@playwright/test';

test('administrator can open Bluem settings', async ({ page }) => {
  await page.goto('/wp-login.php');
  await page.locator('input[name="log"]').fill('wordpress');
  await page.locator('input[name="pwd"]').fill('wordpress');
  await page.locator('#wp-submit').click();

  await page.goto('/wp-admin/admin.php?page=bluem-settings');
  await expect(page.locator('h1')).toContainText('Settings');
  await expect(page.locator('#bluem_woocommerce_settings_senderID')).toBeVisible();
  await expect(page.locator('#bluem_woocommerce_settings_environment')).toBeVisible();
});
