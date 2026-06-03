import re

with open('resources/views/welcome.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Extract the Panel Eksplorasi Manual block
# The block starts at `{{-- ── Panel Eksplorasi Manual ── --}}`
# and ends before `<div id="search-results-container"`
start_marker = "{{-- ── Panel Eksplorasi Manual ── --}}"
end_marker = '<div id="search-results-container"'

if start_marker in content and end_marker in content:
    start_idx = content.find(start_marker)
    end_idx = content.find(end_marker)
    
    panel_html = content[start_idx:end_idx]
    
    # Remove it from current location
    content = content[:start_idx] + content[end_idx:]
    
    # Change max-width from 700px to 100% or 1400px to match search results
    panel_html = panel_html.replace('max-width:700px', 'max-width:1400px')
    
    # Insert it right before <div class="options-grid">
    target_insert = '<div class="options-grid">'
    if target_insert in content:
        content = content.replace(target_insert, panel_html + '\n        ' + target_insert)

# 2. Modify the javascript rendering
old_js = """                            <div class="hierarchy-title">
                                ${node.judul}
                                ${isLeaf && node.contoh_lapangan ? `<div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.4rem; font-weight:normal;">Contoh: ${Array.isArray(node.contoh_lapangan) ? node.contoh_lapangan.join(', ') : node.contoh_lapangan}</div>` : ''}
                            </div>"""

new_js = """                            <div class="hierarchy-title">
                                <div style="font-size:1.05rem; font-weight:700; margin-bottom:0.25rem;">${node.judul}</div>
                                ${node.deskripsi ? `<div style="font-size:0.85rem; color:var(--text-muted); font-weight:normal; line-height:1.4; margin-bottom:0.25rem;">${node.deskripsi}</div>` : ''}
                                ${isLeaf && node.contoh_lapangan ? `<div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.5rem; font-weight:normal; background: rgba(249,115,22,0.05); padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(249,115,22,0.1);"><strong>Contoh Lapangan:</strong> ${Array.isArray(node.contoh_lapangan) ? node.contoh_lapangan.join(', ') : node.contoh_lapangan}</div>` : ''}
                            </div>"""

content = content.replace(old_js, new_js)

with open('resources/views/welcome.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated welcome.blade.php")
