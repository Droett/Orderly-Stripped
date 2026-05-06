import re

with open('LandingPageOrd/styles.css', 'r') as f:
    css = f.read()

# I will manually split by looking at the comments.
# Lines 1-27: Root, Base
# Lines 28-78: NAV
# Lines 79-116: BUTTONS
# Lines 117-216: HERO (index) => wait, hero is used in index. software has .page-hero. pricing has .pricing-hero
# Lines 217-251: STATS BAR (index)
# Lines 252-332: SECTIONS (index mostly, but .section-title, .section-label, .section-sub are shared)
# Lines 333-378: FEATURES (index)
# Lines 379-435: HOW IT WORKS (index)
# Lines 436-487: TESTIMONIALS (index)
# Lines 488-577: PRICING (index) -> .pricing-grid, .pricing-card... wait, the pricing page has .pricing-grid-full.
# Lines 578-609: CTA BANNER (shared CTA on index and software/pricing?)
# Lines 610-669: FOOTER (shared)
# Lines 670-1003: SOFTWARE PAGE
# Lines 1004-1078: RESPONSIVE

global_css = []
index_css = []
software_css = []

# To make it simple, I will read the file line by line and route it
sections = {
    'global': [],
    'index': [],
    'software': []
}
current_section = 'global'

lines = css.split('\n')
for i, line in enumerate(lines):
    if line.startswith('/* HERO */'): current_section = 'index'
    elif line.startswith('/* STATS BAR */'): current_section = 'index'
    elif line.startswith('/* SECTIONS */'): current_section = 'global' # shared utility classes
    elif line.startswith('/* FEATURES */'): current_section = 'index'
    elif line.startswith('/* HOW IT WORKS */'): current_section = 'index'
    elif line.startswith('/* TESTIMONIALS */'): current_section = 'index'
    elif line.startswith('/* PRICING */'): current_section = 'index'
    elif line.startswith('/* CTA BANNER */'): current_section = 'global' # Used on multiple pages
    elif line.startswith('/* FOOTER */'): current_section = 'global'
    elif line.startswith('/* SOFTWARE PAGE */'): current_section = 'software'
    elif line.startswith('/* RESPONSIVE */'): 
        current_section = 'responsive'
        break

    if current_section in sections:
        sections[current_section].append(line)

# Let's handle responsive
responsive_lines = lines[1004:] # from @media (max-width: 900px) {

# Parse the responsive block. It's a single @media query
# Inside it:
# nav, .hero, .hero h1, .hero-visual, .stats-bar, section, .features-grid, .steps::before, .steps, .testimonials-grid, .pricing-grid, .footer-grid, .module-block, .page-hero, .modules-section, .tech-grid, .tech-section

resp_global = ["@media (max-width: 900px) {"]
resp_index = ["@media (max-width: 900px) {"]
resp_software = ["@media (max-width: 900px) {"]

resp_current = []
target_file = None

for line in responsive_lines[1:]:
    if line.strip() == "}":
        # EOF media query
        break
    
    if "{" in line:
        sel = line.split("{")[0].strip()
        if sel in ["nav", "section", ".cta-section", ".footer-grid"]:
            target_file = resp_global
        elif sel in [".hero", ".hero h1", ".hero-visual", ".stats-bar", ".features-grid", ".steps::before", ".steps", ".testimonials-grid", ".pricing-grid"]:
            target_file = resp_index
        elif sel in [".module-block,", ".module-block.reverse", ".page-hero", ".modules-section", ".tech-grid", ".tech-section"]:
            target_file = resp_software
        else:
            # try to guess
            target_file = resp_global
            
    if target_file is not None:
        target_file.append(line)
        if "}" in line:
            # block ended
            pass

resp_global.append("}")
resp_index.append("}")
resp_software.append("}")

with open('LandingPageOrd/global.css', 'w') as f:
    f.write('\n'.join(sections['global']) + '\n' + '\n'.join(resp_global) + '\n')

with open('LandingPageOrd/index.css', 'w') as f:
    f.write('\n'.join(sections['index']) + '\n' + '\n'.join(resp_index) + '\n')

with open('LandingPageOrd/software.css', 'w') as f:
    f.write('\n'.join(sections['software']) + '\n' + '\n'.join(resp_software) + '\n')

print("Split completed successfully.")
