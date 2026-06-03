import sys

with open('resources/views/welcome.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Insert CSS
css_to_insert = """
        /* --- Hierarchy Tree --- */
        .hierarchy-node {
            margin-bottom: 0.5rem;
        }
        .hierarchy-header {
            display: flex;
            align-items: flex-start;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.6);
            border: 1px solid var(--glass-border);
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
            gap: 1rem;
        }
        .hierarchy-header:hover {
            background: rgba(255,255,255,0.9);
            border-color: var(--primary);
            transform: translateX(5px);
        }
        .hierarchy-kode {
            font-weight: 800;
            color: var(--primary);
            min-width: 45px;
        }
        .hierarchy-title {
            flex: 1;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-color);
        }
        .hierarchy-icon {
            color: var(--text-muted);
            transition: transform 0.3s;
        }
        .hierarchy-children {
            padding-left: 2rem;
            margin-top: 0.5rem;
            display: none;
            border-left: 2px dashed rgba(249,115,22,0.3);
            margin-left: 1rem;
        }
        /* Custom scrollbar for hierarchy container */
        #hierarchy-container::-webkit-scrollbar {
            width: 8px;
        }
        #hierarchy-container::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.02);
            border-radius: 4px;
        }
        #hierarchy-container::-webkit-scrollbar-thumb {
            background: rgba(249,115,22,0.2);
            border-radius: 4px;
        }
        #hierarchy-container::-webkit-scrollbar-thumb:hover {
            background: rgba(249,115,22,0.5);
        }
"""
if ".hierarchy-node {" not in content:
    content = content.replace('    </style>', css_to_insert + '    </style>')

# 2. Insert HTML
html_to_insert = """
        {{-- ── Panel Eksplorasi Manual ── --}}
        <div style="width:100%; max-width:700px; margin-top:1.5rem; margin-bottom: 2rem; position: relative; z-index: 20;">
            <button id="toggle-hierarchy-btn" style="width: 100%; padding: 1rem 1.5rem; border-radius: 1.25rem; background: var(--glass); border: 1px solid var(--glass-border); color: var(--text-color); font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: all 0.3s; box-shadow: 0 10px 30px var(--glass-shadow); backdrop-filter: blur(10px);">
                <span style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.2rem; background: rgba(249,115,22,0.1); padding: 0.4rem; border-radius: 0.5rem;">📂</span> Eksplorasi Manual KBLI 2025
                </span>
                <span id="hierarchy-chevron" style="transition: transform 0.3s;">▼</span>
            </button>
            
            <div id="hierarchy-container" style="display: none; margin-top: 0.5rem; background: var(--glass); border-radius: 1.25rem; border: 1px solid var(--glass-border); padding: 1.5rem; box-shadow: 0 15px 35px rgba(0,0,0,0.1); max-height: 60vh; overflow-y: auto; backdrop-filter: blur(10px);">
                <!-- Hierarchy Tree Will Be Injected Here -->
            </div>
        </div>
"""
if "toggle-hierarchy-btn" not in content:
    target_html = """        <div id="search-results-container" style="display: none;">"""
    content = content.replace(target_html, html_to_insert + "\n" + target_html)

# 3. Insert JS
js_to_insert = """
        // --- Hierarchy Drill-down Logic ---
        const hierarchyBtn = document.getElementById('toggle-hierarchy-btn');
        const hierarchyContainer = document.getElementById('hierarchy-container');
        const hierarchyChevron = document.getElementById('hierarchy-chevron');

        if (hierarchyBtn) {
            hierarchyBtn.addEventListener('click', async () => {
                if (hierarchyContainer.style.display === 'none') {
                    hierarchyContainer.style.display = 'block';
                    hierarchyChevron.style.transform = 'rotate(180deg)';
                    if (hierarchyContainer.innerHTML.trim() === '' || hierarchyContainer.innerHTML.includes('Hierarchy Tree Will Be Injected Here')) {
                        hierarchyContainer.innerHTML = '<div class="loading-spinner" style="width:30px;height:30px;border-width:3px;margin:2rem auto;"></div>';
                        await loadHierarchy(null, hierarchyContainer);
                    }
                } else {
                    hierarchyContainer.style.display = 'none';
                    hierarchyChevron.style.transform = 'rotate(0deg)';
                }
            });
        }

        async function loadHierarchy(parentKode, containerElement) {
            try {
                const response = await fetch(`/api/kbli/hierarchy?parent=${parentKode || 'null'}`);
                const nodes = await response.json();
                
                containerElement.innerHTML = ''; // clear loading
                
                if (nodes.length === 0) {
                    containerElement.innerHTML = '<p style="color:var(--text-muted); text-align:center;">Tidak ada sub-data.</p>';
                    return;
                }
                
                nodes.forEach(node => {
                    const nodeDiv = document.createElement('div');
                    nodeDiv.className = 'hierarchy-node';
                    
                    const isLeaf = node.is_leaf;
                    const chevronHtml = isLeaf ? '' : `<span class="hierarchy-icon" id="icon-${node.kode}">▼</span>`;
                    
                    const onClickAttr = isLeaf ? '' : `onclick="toggleHierarchyNode('${node.kode}')"`;
                    const leafStyle = isLeaf ? 'background: rgba(249,115,22,0.05); border-color: rgba(249,115,22,0.2); cursor: default;' : '';
                    
                    nodeDiv.innerHTML = `
                        <div class="hierarchy-header" ${onClickAttr} style="${leafStyle}">
                            <div class="hierarchy-kode">${node.kode}</div>
                            <div class="hierarchy-title">
                                ${node.judul}
                                ${isLeaf && node.contoh_lapangan ? `<div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.4rem; font-weight:normal;">Contoh: ${Array.isArray(node.contoh_lapangan) ? node.contoh_lapangan.join(', ') : node.contoh_lapangan}</div>` : ''}
                            </div>
                            ${chevronHtml}
                        </div>
                        ${isLeaf ? '' : `<div class="hierarchy-children" id="children-${node.kode}"></div>`}
                    `;
                    
                    containerElement.appendChild(nodeDiv);
                });
            } catch (error) {
                containerElement.innerHTML = '<div style="color:red; text-align:center; padding: 1rem;">Gagal memuat data.</div>';
            }
        }

        window.toggleHierarchyNode = async (kode) => {
            const childrenContainer = document.getElementById(`children-${kode}`);
            const icon = document.getElementById(`icon-${kode}`);
            
            if (!childrenContainer) return;

            if (childrenContainer.style.display === 'block') {
                childrenContainer.style.display = 'none';
                if (icon) icon.style.transform = 'rotate(0deg)';
            } else {
                childrenContainer.style.display = 'block';
                if (icon) icon.style.transform = 'rotate(180deg)';
                
                if (childrenContainer.innerHTML.trim() === '') {
                    childrenContainer.innerHTML = '<div class="loading-spinner" style="width:20px;height:20px;border-width:2px;margin:1rem;"></div>';
                    await loadHierarchy(kode, childrenContainer);
                }
            }
        };
"""
if "Hierarchy Drill-down Logic" not in content:
    content = content.replace("    </script>", js_to_insert + "    </script>")

with open('resources/views/welcome.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated welcome.blade.php")
