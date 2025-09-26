# Fusion to Arda.cards CSV Converter

A tool that converts Fusion 360 Tool Library CSV exports to Arda.cards bulk import format.

## Features

- ✅ **De-duplicates tools** by Tool Index (keeps first occurrence)
- ✅ **Clean CSV output** with minimal quoting for readability
- ✅ **Formats item names** as "[Tool Number] - [Description]"
- ✅ **Preserves key fields**: SKU, Supplier, Product URLs
- ✅ **Replaces commas** in descriptions with semicolons to avoid excess quoting
- ✅ **Optional image URL mapping** for tool types

## Available Versions

### 1. Standalone HTML (`standalone_fusion_to_arda_converter.html`)
- Single file, works offline
- Open directly in any web browser
- No installation required

### 2. WordPress Plugin (`fusion-arda-converter-ultimate/`)
- Full WordPress integration
- Admin dashboard for customization
- Edit conversion logic without coding
- Customize colors, text, and styling
- Use with shortcode: `[fusion_arda_converter]`

## Usage

### Standalone Version
1. Open `standalone_fusion_to_arda_converter.html` in a web browser
2. Click "Select Fusion Tool Library CSV"
3. Choose your exported Fusion 360 CSV file
4. Click "Convert to Arda Format"
5. File downloads automatically

### WordPress Plugin
1. Copy `fusion-arda-converter-ultimate/` to `/wp-content/plugins/`
2. Activate in WordPress Admin
3. Add `[fusion_arda_converter]` to any page
4. Customize via Fusion Converter menu in admin

## CSV Format

**Input**: Fusion 360 Tool Library export (173+ columns)

**Output**: Arda.cards import format (10 columns)
```csv
Item Name,Notes,SKU,Supplier,Location,Minimum,Order Quantity,Product URL,Image URL,Color Coding
```

## Project Structure

```
├── standalone_fusion_to_arda_converter.html  # Browser-based converter
├── fusion-arda-converter-ultimate/           # WordPress plugin
│   ├── fusion-arda-converter-ultimate.php    # Main plugin file
│   └── default-logic.js                      # Conversion logic
├── Subtract Swing Library Non Ferrous Only.csv  # Sample Fusion export
└── README.md                                  # This file
```

## Customization

The conversion logic can be customized to:
- Map different tool types to images
- Change field mappings
- Add custom processing rules
- Modify deduplication behavior

For WordPress: Edit via admin dashboard
For standalone: Edit the JavaScript in the HTML file

## License

Private project for Subtract Manufacturing