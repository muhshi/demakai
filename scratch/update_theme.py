import re

file_path = '/Users/saiful/development/demakai/resources/views/welcome.blade.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace root variables
content = content.replace('--primary: #4f46e5;', '--primary: #f97316;')
content = content.replace('--primary-glow: rgba(79, 70, 229, 0.2);', '--primary-glow: rgba(249, 115, 22, 0.2);')
content = content.replace('--secondary: #9333ea;', '--secondary: #f59e0b;')
content = content.replace('--secondary-glow: rgba(147, 51, 234, 0.2);', '--secondary-glow: rgba(245, 158, 11, 0.2);')

# Replace badges and hardcoded colors
content = content.replace('rgba(99, 102, 241, 0.15)', 'rgba(249, 115, 22, 0.15)')
content = content.replace('rgba(99, 102, 241, 0.1)', 'rgba(249, 115, 22, 0.1)')
content = content.replace('rgba(99, 102, 241, 0.2)', 'rgba(249, 115, 22, 0.2)')
content = content.replace('rgba(168, 85, 247, 0.1)', 'rgba(245, 158, 11, 0.1)')

# Method panel bad hardcoded color
content = content.replace('rgba(79,70,229,0.15)', 'rgba(249,115,22,0.15)')
content = content.replace('rgba(79,70,229,0.1)', 'rgba(249,115,22,0.1)')
content = content.replace('rgba(79,70,229,0.3)', 'rgba(249,115,22,0.3)')

# Layout updates for centering method panel content
old_panel_title = '<div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">'
new_panel_title = '<div style="display:flex; justify-content:center; align-items:center; gap:0.5rem; margin-bottom:0.75rem;">'
content = content.replace(old_panel_title, new_panel_title)

old_buttons_wrapper = '<div style="display:flex; flex-wrap:wrap; gap:0.5rem;">'
new_buttons_wrapper = '<div style="display:flex; flex-wrap:wrap; justify-content:center; gap:0.5rem;">'
content = content.replace(old_buttons_wrapper, new_buttons_wrapper)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated welcome.blade.php successfully")
