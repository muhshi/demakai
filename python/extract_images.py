import re

with open("Jurnal/full_paper_pintar_kbli.md", "r", encoding="utf-8") as f:
    content = f.read()

# Extract all image definitions at the bottom
image_defs = re.findall(r'(\[image\d+\]:\s*data:image/[^\n]+)', content)
print(f"Found {len(image_defs)} image definitions.")
with open("python/output/extracted_images.txt", "w", encoding="utf-8") as f:
    for img in image_defs:
        f.write(img + "\n\n")
print("Saved extracted images.")
