# WooCommerce SecPaid Payment Gateway

<img width="147" alt="Screenshot 2025-03-12 at 20 17 51" src="https://github.com/user-attachments/assets/65c7fa74-e0d5-43f3-b7e3-10989d036225" />


SecPaid offers a variety of Payment Providers for your WooCommerce store. This plugin is easy to setup, integrate, and use - making it the best solution for your online store.

## Features

- **Multiple Payment Providers**: Access a wide range of payment options through a single integration
- **Easy Setup**: Simple configuration process to get you accepting payments quickly
- **Secure Transactions**: Industry-standard security protocols to protect your customers' data
- **Customizable Checkout**: Tailor the payment experience to match your brand
- **Detailed Transaction Reporting**: Track all payments through an intuitive dashboard

## Installation

### Using The WordPress Dashboard

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'SecPaid Woocommerce Plugin'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

### Uploading in WordPress Dashboard

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `SecPaid-Woocommerce-Plugin.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

### Using FTP

1. Download `SecPaid-Woocommerce-Plugin.zip`
2. Extract the `SecPaid-Woocommerce-Plugin` directory to your computer
3. Upload the `SecPaid-Woocommerce-Plugin` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

## SecPaid Setup

To start accepting payments with SecPaid, follow these steps:

1. **Create a SecPaid Account**: Visit [SecPaid.com](https://secpaid.com) and sign up for an account
2. **Login to Your Dashboard**: Access your merchant dashboard at [dashboard.secpaid.com](https://dashboard.secpaid.com)
3. **Retrieve Your API Key**: Navigate to Settings → API Keys and copy your API key
4. **Set Your Callback URL**: Configure your callback URL in the SecPaid dashboard to: `https://your-site.com/wc-api/SecPaid/`
5. **Configure Payment Endpoint**: Set your payment endpoint to receive transaction notifications

> **Important**: Keep your API keys secure and never share them publicly!
<img width="623" alt="Screenshot 2025-03-12 at 20 17 44" src="https://github.com/user-attachments/assets/78e17f71-13f4-44ee-b1c5-2fc7942924f2" />

## Configuration

1. Go to WooCommerce → Settings → Payments
2. Click on "SecPaid" to configure the payment gateway
3. Enable the payment method and enter your API credentials
4. Customize the payment gateway title and description that customers will see at checkout
5. Save your changes

## Customer Experience

### Customer Message
A gateway description appears to the customer at the Checkout page to provide additional information about the SecPaid payment gateway.

### Customer Note
A note for the customer with further instructions displayed after the checkout process.
<img width="1117" alt="Screenshot 2025-03-12 at 20 16 59" src="https://github.com/user-attachments/assets/2625d39e-0277-4d15-935f-52c7f45c207c" />

## Debugging Mode

The debug mode is an excellent tool to test the plugin's settings and checkout process. When enabled, the payment gateway will only be activated for administrators, allowing you to test the payment flow without affecting regular customers.

## Screenshots
<img width="554" alt="Screenshot 2025-03-12 at 20 17 17" src="https://github.com/user-attachments/assets/c6a7b522-ef69-4959-a08e-fbd736aa05b6" />

![Checkout Page Preview](https://secpaid.com/wp-content/uploads/2023/05/checkout-preview.png)

![Payment Gateway Settings Page](https://secpaid.com/wp-content/uploads/2023/05/settings-page.png)

![Order Notes](https://secpaid.com/wp-content/uploads/2023/05/order-notes.png)

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
