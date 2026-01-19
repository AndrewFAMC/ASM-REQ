# Inventory Tag Printing Guide

## Overview
This guide explains how to print inventory tags exactly as they appear on screen, with all formatting, colors, and layout preserved.

## What Was Changed

### 1. **Updated Print CSS Styles**
The print styles in [custodian/dashboard.php](../custodian/dashboard.php) have been optimized to:
- Use A4 landscape format instead of fixed 150mm x 100mm
- Preserve all background colors using `print-color-adjust: exact`
- Maintain exact font sizes, spacing, and layout
- Ensure QR codes and barcodes print correctly
- Keep all borders and table structure intact

### 2. **Key Improvements**

#### Background Colors Preserved
```css
-webkit-print-color-adjust: exact !important;
print-color-adjust: exact !important;
color-adjust: exact !important;
```
This ensures the gray backgrounds on header cells are printed exactly as shown on screen.

#### Layout Preservation
- All flex layouts are preserved
- Spacing (padding/margins) matches screen display
- Font sizes remain consistent
- Table borders and structure maintained

## How to Print

### Method 1: Print to Physical Printer (Recommended)

1. **Open the inventory tag** by clicking on any asset
2. **Click the "Print" button** in the modal
3. **In the print dialog:**
   - Select your printer
   - Choose **Landscape** orientation
   - Enable **Background graphics** (IMPORTANT!)
   - Set margins to "Default" or "Minimum"
4. **Click Print**

### Method 2: Save as PDF (Best for Digital Archives)

1. **Open the inventory tag** by clicking on any asset
2. **Click the "Print" button** in the modal
3. **In the print dialog:**
   - Select **"Save as PDF"** or **"Microsoft Print to PDF"**
   - Choose **Landscape** orientation
   - Enable **Background graphics** (IMPORTANT!)
   - Set margins to "Default"
4. **Save the PDF**

This method provides:
- Pixel-perfect reproduction
- Digital archiving capability
- Easy sharing via email
- No printer required for preview

### Method 3: Browser Print Function

1. **Open the inventory tag** modal
2. **Press Ctrl+P** (or Cmd+P on Mac)
3. **Configure print settings** as above
4. **Print or Save as PDF**

## Browser-Specific Instructions

### Google Chrome / Microsoft Edge
1. Click Print button or press Ctrl+P
2. **IMPORTANT:** Check **"Background graphics"** under "More settings"
3. Set Layout to **Landscape**
4. Choose destination (printer or Save as PDF)
5. Click Print

### Mozilla Firefox
1. Click Print button or press Ctrl+P
2. **IMPORTANT:** Check **"Print backgrounds"** in settings
3. Set Orientation to **Landscape**
4. Choose destination
5. Click Print

### Safari (Mac)
1. Click Print button or press Cmd+P
2. In the print dialog, go to Safari menu
3. Check **"Print backgrounds"**
4. Set orientation to **Landscape**
5. Click Print

## Troubleshooting

### Background colors not printing
**Solution:** Make sure "Background graphics" or "Print backgrounds" is enabled in your print settings.

### Tag is cut off or too small
**Solution:**
- Check that orientation is set to **Landscape**
- Try adjusting margins to "Minimum"
- Ensure scale is set to 100%

### QR code or barcode not visible
**Solution:**
- Enable "Background graphics"
- Try printing to PDF first to verify
- Check printer quality settings

### Colors look different when printed
**Solution:**
- This is normal due to printer calibration
- Use "Save as PDF" for consistent digital copies
- Consider professional printing services for high-quality physical tags

### Layout is different from screen
**Solution:**
- Clear browser cache (Ctrl+Shift+Delete)
- Refresh the page (F5)
- Try a different browser (Chrome recommended)

## Technical Details

### Page Specifications
- **Format:** A4 Landscape
- **Margins:** 10mm all sides
- **Tag Size:** Approximately 800px wide x 400px tall
- **Border:** 2px solid black
- **Font:** Arial, sans-serif

### Elements Included in Print
✅ HCC Logo
✅ All table data and labels
✅ Gray background colors on headers
✅ QR code
✅ Barcode
✅ All borders and spacing
✅ "INVENTORY TAG" vertical text
✅ "Attach This Style" text

### Elements Hidden in Print
❌ Print button
❌ Close button
❌ Modal background
❌ Page header/footer

## Best Practices

1. **Always preview before printing** - Use Print Preview to check layout
2. **Enable background graphics** - Critical for proper appearance
3. **Use landscape orientation** - Tag is designed for landscape
4. **Save digital copies** - Print to PDF for records
5. **Test with different browsers** - Chrome/Edge recommended
6. **Check printer settings** - Ensure quality is set to "Normal" or higher

## For Label Printers

If you're using a dedicated label printer (e.g., Dymo, Brother):

1. **Save as PDF** first using the instructions above
2. **Open PDF** in your label printer software
3. **Adjust to label size** if needed
4. **Print** using label printer

## Support

If you encounter issues:
1. Try a different browser (Chrome recommended)
2. Check printer drivers are up to date
3. Verify printer supports color printing
4. Test with "Save as PDF" first

## Summary

The updated print system now:
- ✅ Matches screen display exactly
- ✅ Preserves all colors and backgrounds
- ✅ Maintains proper layout and spacing
- ✅ Works across all major browsers
- ✅ Supports both printing and PDF export

**Remember:** Always enable "Background graphics" in your print settings for best results!
