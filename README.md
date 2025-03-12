# WooCommerce SecPaid Payment Gateway

/**
 * Plugin Name: WooCommerce Custom Payment Gateway
 * Plugin URI: https://secpaid.com
 * Description: SecPaid Payment Gateway for WooCommerce
 * Version: 1.0
 * Author: WPRuby, Ala Eddin Eltai
 * Author URI: https://secpaid.com, https://wpruby.com
 */

![168959749](https://github.com/user-attachments/assets/5174d5ce-7ed9-4181-8158-13f8cee4d806)

<img width="170" alt="Screenshot 2025-03-12 at 20 17 51" src="https://github.com/user-attachments/assets/65c7fa74-e0d5-43f3-b7e3-10989d036225" />


SecPaid offers a variety of Payment Providers for your WooCommerce store. This plugin is easy to setup, integrate, and use - making it the best solution for your online store.

## Features

- **Multiple Payment Providers**: Access a wide range of payment options through a single integration
- **Easy Setup**: Simple configuration process to get you accepting payments quickly
- **Secure Transactions**: Industry-standard security protocols to protect your customers' data
- **Customizable Checkout**: Tailor the payment experience to match your brand
- **Detailed Transaction Reporting**: Track all payments through an intuitive dashboard

## Installation


### Uploading in WordPress Dashboard

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `SecPaid-Woocommerce-Plugin.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

<img width="477" alt="Screenshot 2025-03-12 at 20 20 22" src="https://github.com/user-attachments/assets/444485da-877a-4dd8-b7d6-1b7945e0fedb" />


### Using FTP

1. Download `SecPaid-Woocommerce-Plugin.zip`
2. Extract the `SecPaid-Woocommerce-Plugin` directory to your computer
3. Upload the `SecPaid-Woocommerce-Plugin` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

## SecPaid Setup

To start accepting payments with SecPaid, follow these steps:

1. **Create a SecPaid Account**: Visit [SecPaid.com](https://app.secpaid.com) and sign up for an account
2. **Login to Your Dashboard**: Access your merchant dashboard at [app.secpaid.com/settings](https://app.secpaid.com)
3. **Retrieve Your API Key**: Navigate to Settings → API Keys and copy your API key
4. **Set Your Callback URL**: Configure your callback URL in the SecPaid dashboard to: https://your-webshop.com/wc-api/secpaid-callback
5. **Configure Payment Endpoint**: Set your payment endpoint to receive transaction notifications: https://your-webshop.com/wp-json/secpaid/v1/webhook

> **Important**: Keep your API keys secure and never share them publicly!
<img width="623" alt="Screenshot 2025-03-12 at 20 17 44" src="https://github.com/user-attachments/assets/78e17f71-13f4-44ee-b1c5-2fc7942924f2" />

## Configuration

1. Go to WooCommerce → Settings → Payments
2. Click on "SecPaid" to configure the payment gateway
3. Select the Type of Endpoint you want to choose for your payments (Splits, Basic)
4. Enable the payment method and enter your API credentials
5. Customize the payment gateway title and description that customers will see at checkout (You can for example add/delete payment options after contacitung the SecPaid Team.
6. Save your changes

<img width="1302" alt="Screenshot 2025-03-12 at 20 21 08" src="https://github.com/user-attachments/assets/6199ae4d-1f65-4f6e-897f-d84c066be391" />


## Custom Editing

### Customer Message
A gateway description appears to the customer at the Checkout page to provide additional information about the SecPaid payment gateway.

### Customer Note
A note for the customer with further instructions displayed after the checkout process.
<img width="1117" alt="Screenshot 2025-03-12 at 20 16 59" src="https://github.com/user-attachments/assets/2625d39e-0277-4d15-935f-52c7f45c207c" />

## Debugging Mode

The debug mode is an excellent tool to test the plugin's settings and checkout process. When enabled, the payment gateway will only be activated for administrators, allowing you to test the payment flow without affecting regular customers.

## Screenshots

<img width="554" alt="Screenshot 2025-03-12 at 20 17 17" src="https://github.com/user-attachments/assets/c6a7b522-ef69-4959-a08e-fbd736aa05b6" />
<img width="1417" alt="Screenshot 2025-03-12 at 20 28 17" src="https://github.com/user-attachments/assets/3ea9c910-d1ed-4caa-b721-e1471b4a9546" />
<img width="1230" alt="Screenshot 2025-03-12 at 20 28 31" src="https://github.com/user-attachments/assets/c866cd6f-c364-4ed0-a69c-1c26c76b3ce1" />
<img width="1031" alt="Screenshot 2025-03-12 at 20 29 01" src="https://github.com/user-attachments/assets/6096ea32-d621-46f8-af95-7eae55fa3316" />

## Changelog

### 1.5.0
* Forked: SecPaid Forked from WooCommerce Custom Payment Gateway (Other Payment Gateway) by WPRuby

### 1.4.0
* Added: WooCommerce Checkout Blocks support
* Fixed: Validation of payment textarea was required even if the textarea is hidden

### 1.3.11
* Added: WordPress 6.7 compatibility

### 1.3.10
* Fixed: PHP deprecation notice
* Added: WordPress 6.6 compatibility
* Added: WooCommerce 9.1 compatibility

*See full changelog in plugin documentation*

## Support

For questions or support, please contact our team at support@secpaid.com or visit our [support portal](https://support.secpaid.com).

## License

This plugin is licensed under GPLv2 or later.
[License URI](http://www.gnu.org/licenses/gpl-2.0.html)
