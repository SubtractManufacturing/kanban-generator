// Process and deduplicate rows
// First, deduplicate by Tool Index
var seen = {};
var dedupedData = [];

csvData.forEach(function(row) {
    // Get tool index for deduplication
    var toolIndex = String(row['Tool Index (tool_index)'] || '').trim();

    // Create a unique key - use tool index if available, otherwise use full row
    var key = toolIndex && toolIndex.toLowerCase() !== 'nan' && toolIndex !== ''
        ? toolIndex
        : JSON.stringify(row);

    // Only add if we haven't seen this tool before
    if (!seen[key]) {
        seen[key] = true;
        dedupedData.push(row);
    }
});

// Now process the deduplicated data
return dedupedData.map(function(row) {
    // Define image URL mappings INSIDE the function
    var imageUrls = {
/*        'flat end mill': 'https://cdn.mscdirect.com/global/images/ProductImages/6085135AA-24.jpg',
        'ball end mill': 'https://cdn.mscdirect.com/global/images/ProductImages/2976776-24.jpg',
        'chamfer mill': 'https://cdn.mscdirect.com/global/images/ProductImages/4805697-24.jpg',
        'drill': 'https://cdn.mscdirect.com/global/images/ProductImages/7851792-21.jpg',
        'tap right hand': 'https://cdn.mscdirect.com/global/images/ProductImages/4122844-24.jpg',
        'spot drill': 'https://cdn.mscdirect.com/global/images/ProductImages/4540303-24.jpg',
        'bull nose end mill': 'https://cdn.mscdirect.com/global/images/ProductImages/8695501-21.jpg',
        'thread mill': 'https://cdn.mscdirect.com/global/images/ProductImages/7604790-24.jpg',
        'slot mill': 'https://cdn.mscdirect.com/global/images/ProductImages/7306018-24.jpg',
        'dovetail mill': 'https://cdn.mscdirect.com/global/images/ProductImages/0182832-24.jpg'
*/
    };

    // Default image - set to empty string if you don't want a default
    var defaultImg = '';

    // Extract values
    var desc = String(row['Description (tool_description)'] || '').trim();
    // Remove commas from description to avoid CSV parsing issues
    // Also remove any quotes that might be in the description
    desc = desc.replace(/,/g, '').replace(/"/g, '');
    var num = String(row['Number (tool_number)'] || '').trim();

    // Normalize 'nan' strings
    if (num.toLowerCase() === 'nan') num = '';

    // Build item name
    var itemName = '';
    if (num && desc) {
        itemName = num + ' - ' + desc;
    } else {
        itemName = num || desc;
    }

    // Get tool type and map to image
    var toolType = String(row['Type (tool_type)'] || '').toLowerCase().trim();

    // Get image URL: use mapped URL if exists, otherwise use default (which can be empty)
    var imgUrl = imageUrls[toolType] || defaultImg;

    // Return Arda format object for this row
    return {
        'Item Name': itemName,
        'Notes': row['Comment (tool_comment)'] || '',
        'SKU': row['Product ID (tool_productId)'] || '',
        'Supplier': row['Vendor (tool_vendor)'] || '',
        'Location': '',
        'Minimum': '',
        'Order Quantity': '',
        'Product URL': row['Product Link (tool_productLink)'] || '',
        'Image URL': imgUrl,
        'Color Coding': ''
    };
});