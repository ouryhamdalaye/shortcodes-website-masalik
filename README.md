# WordPress Shortcodes Collection

This repository contains a collection of custom WordPress shortcodes for articles and WooCommerce products. These shortcodes are designed to be reusable and can be easily integrated into any WordPress theme.

## 📋 Available Shortcodes

### Articles Shortcodes

#### `[articles_mansory]`
A masonry layout displaying articles and categories in a responsive grid format.

**Parameters:**
- `per_page="18"` - Number of items per page
- `posts_ratio="0.66"` - Ratio of posts vs categories (0.66 = 66% posts, 34% categories)
- `categories="category"` - Taxonomy to use for categories
- `exclude_cat=""` - Comma-separated category slugs/IDs to exclude
- `exclude_posts=""` - Comma-separated post slugs/IDs to exclude

**Example:**
```
[articles_mansory per_page="12" posts_ratio="0.7" exclude_cat="uncategorized"]
```

#### `[articles_categories]`
A horizontal, discrete list of parent categories with icons and hub links.

**Parameters:**
- `taxonomy="category"` - Taxonomy to use
- `exclude_cat=""` - Comma-separated category slugs/IDs to exclude
- `limit="10"` - Maximum number of categories to display

**Example:**
```
[articles_categories limit="8" exclude_cat="uncategorized,news"]
```

#### `[articles_featured_home]`
Displays featured articles in a grid layout for homepage use.

#### `[articles_featured_vertical_list]`
A vertical list of featured articles with clean card design.

#### `[articles_category_panels]`
Category panels with icons and descriptions.

#### `[articles_category_hub]`
Category hub layout for organizing content.

### WooCommerce Shortcodes

#### `[wc_cards]`
Product cards with variation swatches and interactive features.

#### `[wc_book_of_month]`
Special layout for "book of the month" products.

#### `[wc_cats_icons]`
Horizontal scrolling category icons for WooCommerce products.

## 🚀 Installation

1. Upload the files to your WordPress theme's directory
2. Include the shortcode files in your theme's `functions.php` or create a plugin
3. The CSS will be automatically enqueued when shortcodes are used

## 📁 File Structure

```
WPRemoteFiles/
├── inc/
│   ├── shortcodes/
│   │   ├── articles-mansory.php
│   │   ├── articles-categories.php
│   │   ├── articles-featured-home.php
│   │   ├── articles-featured-vertical-list.php
│   │   ├── articles-category-panels.php
│   │   ├── articles-category-hub.php
│   │   ├── products-wc-cards.php
│   │   ├── products-wc-book-of-month.php
│   │   └── products-wc-cats-icons.php
│   └── assets.php
├── assets/
│   ├── css/
│   │   ├── articles-mansory.css
│   │   ├── articles-categories.css
│   │   ├── articles-featured-home.css
│   │   ├── articles-featured-vertical-list.css
│   │   ├── articles-category-panels.css
│   │   ├── articles-category-hub.css
│   │   ├── wc-cards.css
│   │   ├── wc-book-of-month.css
│   │   └── wc-cats-icons.css
│   └── js/
│       └── wc-cards.js
├── functions.php
└── style.css
```

## 🎨 Features

- **Responsive Design**: All shortcodes are mobile-friendly
- **ACF Integration**: Supports Advanced Custom Fields for icons and custom data
- **Pagination**: Built-in pagination for content-heavy shortcodes
- **Customizable**: Easy to modify colors, spacing, and layout
- **Performance Optimized**: Efficient queries and minimal CSS/JS
- **Accessibility**: Proper ARIA labels and semantic HTML

## 🔧 Dependencies

- **WordPress**: 5.0+
- **Advanced Custom Fields (ACF)**: For custom fields support (optional)
- **WooCommerce**: For product-related shortcodes

## 📝 Usage Examples

### Basic Article Masonry
```
[articles_mansory]
```

### Custom Article Categories
```
[articles_categories taxonomy="product_cat" limit="6"]
```

### Featured Articles with Custom Settings
```
[articles_featured_home per_page="6" categories="featured"]
```

## 🎯 Customization

### CSS Variables
Many shortcodes use CSS custom properties for easy theming:

```css
:root {
  --gcid-primary-color: #013C40;
  --gcid-secondary-color: #53B982;
}
```

### ACF Fields
The shortcodes expect these ACF fields:
- `featured` (true/false) - For featured articles
- `author` (text) - Article author
- `read_time` (text) - Reading time
- `term_icon` (image) - Category icon
- `lien_hub` (URL) - Category hub link

## 📄 License

This project is free to use and modify. Feel free to reuse these shortcodes in your WordPress projects.

## 🤝 Contributing

Contributions are welcome! Feel free to submit issues or pull requests to improve these shortcodes.

## 📞 Support

For questions or support, please open an issue in this repository.

---

**Note**: These shortcodes were originally developed for the Masalik.fr website but are designed to be reusable across different WordPress projects.
