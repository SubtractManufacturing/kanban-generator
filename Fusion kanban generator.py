"""
Fusion → Arda.cards Converter (with GUI pickers + image URL mapping)

WHAT THIS DOES
--------------
- Reads a Fusion 360 Tool Library CSV and converts it into an Arda.cards bulk import CSV.
- De-duplicates tools by "Tool Index (tool_index)" (keeps the first occurrence).
- Sets:
    * Item Name = "<tool_number> - <Description (tool_description)>" (or just Description if number is missing)
    * Notes     = "Comment (tool_comment)" (only)
    * SKU       = "Product ID (tool_productId)"
    * Supplier  = "Vendor (tool_vendor)"
    * Product URL = "Product Link (tool_productLink)"
    * Image URL   = based on "Type (tool_type)" using the mapping below; if not matched, uses DEFAULT_IMAGE_URL
    * Location, Minimum, Order Quantity = left blank
    * Color Coding = left blank
- Always uses the fixed Arda format below.
- Presents a GUI to select the input Fusion CSV and the output CSV save path.

CUSTOMIZE IMAGE URLS
--------------------
Update the IMAGE_URLS below if needed. Keys must be the lowercased values found in Fusion's "Type (tool_type)" column.
"""

import pandas as pd
from pathlib import Path

# =============================================================================
# DEFAULT / PLACEHOLDER IMAGE URL (used when tool_type not in IMAGE_URLS)
# =============================================================================
DEFAULT_IMAGE_URL = "https://www.fictiv.com/wp-content/uploads/2021/05/image2-1-1536x864.jpg"

# =============================================================================
# IMAGE URL MAPPING (EDIT THESE IF YOU LIKE)
# =============================================================================
IMAGE_URLS = {
    "flat end mill":       "https://cdn.mscdirect.com/global/images/ProductImages/6085135AA-24.jpg",
    "ball end mill":       "https://cdn.mscdirect.com/global/images/ProductImages/2976776-24.jpg",
    "chamfer mill":        "https://cdn.mscdirect.com/global/images/ProductImages/4805697-24.jpg",
    "drill":               "https://cdn.mscdirect.com/global/images/ProductImages/7851792-21.jpg",
    "tap right hand":      "https://cdn.mscdirect.com/global/images/ProductImages/4122844-24.jpg",
    "spot drill":          "https://cdn.mscdirect.com/global/images/ProductImages/4540303-24.jpg",
    "bull nose end mill":  "https://cdn.mscdirect.com/global/images/ProductImages/8695501-21.jpg",
    "thread mill":         "https://cdn.mscdirect.com/global/images/ProductImages/7604790-24.jpg",
    "slot mill":           "https://cdn.mscdirect.com/global/images/ProductImages/7306018-24.jpg",
    "dovetail mill":       "https://cdn.mscdirect.com/global/images/ProductImages/0182832-24.jpg",
}

# =============================================================================
# FIXED ARDA FORMAT (always used, never changes)
# =============================================================================
ARDA_COLS = [
    "Item Name",
    "Notes",
    "SKU",
    "Supplier",
    "Location",
    "Minimum",
    "Order Quantity",
    "Product URL",
    "Image URL",
    "Color Coding",
]

# =============================================================================
# CORE CONVERSION
# =============================================================================
def fusion_to_arda(fusion_csv_path, out_csv_path="arda_import.csv"):
    """
    Convert a Fusion 360 tool library CSV into an Arda.cards bulk import CSV.

    Rules:
      - De-duplicate by "Tool Index (tool_index)" (keep first occurrence).
      - Item Name  = "<tool_number> - <Description (tool_description)>" (or just Description if number missing).
      - Notes      = "Comment (tool_comment)" ONLY.
      - SKU        = "Product ID (tool_productId)".
      - Supplier   = "Vendor (tool_vendor)".
      - Product URL= "Product Link (tool_productLink)".
      - Image URL  = selected by "Type (tool_type)" using IMAGE_URLS; falls back to DEFAULT_IMAGE_URL if not matched.
      - Location, Minimum, Order Quantity, Color Coding = left blank.
      - If a tool has NO tool number, it is NOT prepended to the Item Name and is NOT used anywhere else.
    """
    fusion_csv_path = Path(fusion_csv_path)
    out_csv_path = Path(out_csv_path)

    # --- Load Fusion CSV ---
    df = pd.read_csv(fusion_csv_path, encoding="utf-8", engine="python")

    # --- Known Fusion column labels (as seen in typical Fusion exports) ---
    COL = {
        "tool_index":       "Tool Index (tool_index)",
        "tool_description": "Description (tool_description)",
        "tool_number":      "Number (tool_number)",
        "product_id":       "Product ID (tool_productId)",
        "product_link":     "Product Link (tool_productLink)",
        "vendor":           "Vendor (tool_vendor)",
        "tool_comment":     "Comment (tool_comment)",
        "tool_type":        "Type (tool_type)",
    }

    # --- De-duplicate by Tool Index (keep first) ---
    tool_index_col = COL["tool_index"]
    if tool_index_col in df.columns:
        # Normalize index to string for consistent deduplication
        df[tool_index_col] = df[tool_index_col].astype(str).str.strip()
        # Treat string "nan" as empty to avoid false duplicates
        df[tool_index_col] = df[tool_index_col].replace({"nan": ""})
        # Keep the first occurrence
        df = df.drop_duplicates(subset=[tool_index_col], keep="first")

    # Helper: safe getter
    def get(row, col_name, default=""):
        return row[col_name] if col_name in row and pd.notna(row[col_name]) else default

    # Helper: pick image URL by tool type (fallback to DEFAULT_IMAGE_URL)
    def image_url_for_type(tool_type_value: str) -> str:
        if not isinstance(tool_type_value, str):
            return DEFAULT_IMAGE_URL
        key = tool_type_value.strip().lower()
        return IMAGE_URLS.get(key, DEFAULT_IMAGE_URL)

    # --- Build output rows ---
    out_rows = []
    for _, row in df.iterrows():
        desc = get(row, COL["tool_description"]).strip() if isinstance(get(row, COL["tool_description"]), str) else get(row, COL["tool_description"])
        number_raw = get(row, COL["tool_number"])
        number = str(number_raw).strip() if pd.notna(number_raw) else ""
        # Normalize "nan" strings and empty to truly empty
        if not number or number.lower() == "nan":
            number = ""

        # Item Name:
        # - If number exists → "<number> - <desc>" (when desc present) or "<number>".
        # - If number missing → just "<desc>".
        item_name = f"{number} - {desc}" if (number and desc) else (number or desc)

        # Notes: only Fusion "Comment"
        notes = get(row, COL["tool_comment"])

        # SKU: Product ID ONLY (do not fall back to tool number)
        sku = get(row, COL["product_id"])

        # Supplier: Vendor
        supplier = get(row, COL["vendor"])

        # Product URL: Product Link
        product_url = get(row, COL["product_link"])

        # Image URL: map from tool type (fallback to DEFAULT_IMAGE_URL)
        tool_type_val = get(row, COL["tool_type"])
        image_url = image_url_for_type(tool_type_val)

        # Location, Minimum, Order Quantity, Color Coding: blank
        location = ""
        minimum = ""
        order_qty = ""
        color = ""

        # Assemble in Arda schema
        assembled = {
            "Item Name": item_name,
            "Notes": notes,
            "SKU": sku,
            "Supplier": supplier,
            "Location": location,
            "Minimum": minimum,
            "Order Quantity": order_qty,
            "Product URL": product_url,
            "Image URL": image_url,
            "Color Coding": color,
        }

        out_rows.append({col: assembled.get(col, "") for col in ARDA_COLS})

    out_df = pd.DataFrame(out_rows, columns=ARDA_COLS)
    out_df.to_csv(out_csv_path, index=False, encoding="utf-8")
    return out_csv_path

# =============================================================================
# SIMPLE GUI
# =============================================================================
def run_gui():
    import tkinter as tk
    from tkinter import filedialog, messagebox

    root = tk.Tk()
    root.withdraw()
    root.update()

    try:
        # 1) Ask for Fusion CSV
        fusion_path = filedialog.askopenfilename(
            title="Select Fusion Tool Library CSV",
            filetypes=[("CSV files", "*.csv"), ("All files", "*.*")]
        )
        if not fusion_path:
            messagebox.showwarning("Cancelled", "No Fusion CSV selected. Exiting.")
            return

        # 2) Ask where to save the Arda CSV
        save_path = filedialog.asksaveasfilename(
            title="Save Arda Import CSV As",
            defaultextension=".csv",
            initialfile="arda_import.csv",
            filetypes=[("CSV files", "*.csv"), ("All files", "*.*")]
        )
        if not save_path:
            messagebox.showwarning("Cancelled", "No save location selected. Exiting.")
            return

        # 3) Convert
        out_path = fusion_to_arda(fusion_path, save_path)
        messagebox.showinfo("Success", f"Arda CSV written to:\n{out_path}")

    except Exception as e:
        messagebox.showerror("Error", f"Something went wrong:\n{e}")
        raise
    finally:
        try:
            root.destroy()
        except Exception:
            pass

if __name__ == "__main__":
    # If run directly, launch the GUI picker flow (input + save only).
    run_gui()
