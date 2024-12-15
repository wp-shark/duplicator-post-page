const { test, expect } = require('@playwright/test');

test.describe('Duplicator Post Page Plugin Tests', () => {
  // Test Setup
  const adminUrl = 'http://localhost/wptester/wp-admin'; // Replace with your WP admin URL
  const adminUser = 'admin'; // Replace with your admin username
  const adminPass = '123'; // Replace with your admin password

  test('Activate Plugin and Verify Functionality', async ({ page }) => {
	// Login to WordPress Admin
	await page.goto(adminUrl);
	await page.fill('#user_login', adminUser);
	await page.fill('#user_pass', adminPass);
	await page.click('#wp-submit');
  
	// Verify successful login
	await expect(page).toHaveURL(new RegExp(adminUrl + '/?$'));  // Allow trailing slash
  
	// Navigate to Plugins Page
	await page.goto(adminUrl + '/plugins.php');
  
	// Activate the plugin (skip activation check for now)
	const pluginRow = await page.locator('tr[data-slug="duplicator-post-page"]'); // Adjust slug as needed
	const activateButton = pluginRow.locator('a.activate');
	if (await activateButton.isVisible()) {
	  await activateButton.click();
	  // Wait for the page to reload after plugin activation
	  await page.waitForNavigation({ waitUntil: 'networkidle' });
	}
  
	// Navigate to Posts Page
	await page.goto(adminUrl + '/edit.php');
  
	// Duplicate a post
	const postRow = await page.locator('tbody tr:first-child');
	const duplicateLink = postRow.locator('a:has-text("Duplicate")'); // Update text based on your plugin
	await expect(duplicateLink).toBeVisible();
  
	// Click Duplicate and verify new post
	await duplicateLink.click();
	await page.waitForNavigation();
	const duplicatedPost = await page.locator('tbody tr:first-child td.title');
	await expect(duplicatedPost).toContainText('Copy of'); // Update as per duplication naming logic
  
	// Validate custom meta (if applicable)
	await page.goto(adminUrl + '/plugins.php');
	const metaLink = pluginRow.locator('a:has-text("Video Tutorials")');
	await expect(metaLink).toBeVisible();
	await expect(metaLink).toHaveAttribute('href', 'https://www.youtube.com/watch?v=GzBJW-NE1l8');
  });
  
});
