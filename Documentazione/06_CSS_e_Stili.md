# Mappatura Stili (CSS)

Tutti gli stili sono Vanilla CSS, distribuiti modularmente secondo l'ambito di utilizzo ma legati insieme da un solido set di variabili primarie CSS.

## 1. `common.css` (Base System e Temi)
Contiene il reset globale, i font (Inter/Google Fonts) e definisce nell'elemento primario `:root` la palette dei colori per i bottoni (rosso `#ff3344`, grigiastri, varianti shadow).
Gestisce in via nativa e profonda il Dark Mode. Se un utente clica "Luna", il CSS media query si interfaccia con le classi iniettate globalmente per rigirare colori dello sfondo, variare la visibilità degli HUD e attenuare i gradienti accesi del rosso (che al buio darebbero fastidio agli occhi) rendendoli rossastri opachi via CSS variables fallback.

## 2. `login.css` (Accoglienza Pubblica)
Stili localizzati ad estinguere i bordi o squadrare il box di Indexing. Introduce effetti glassmorphism (sfocatura leggera).

## 3. `manager.css`
Cura lo scaling grigliato delle dashboard. Usa densamente variabili layout `flexbox`. Il design adotta un menù laterale fisso e uno stage fluido centrale (il Main layout container).

## 4. `tavolo.css`
Dedicato all'esperienza Mobile/PWA (è la dashboard usata dai commensali dal telefono).
Molla il classico menù desktop asinistra (`manager-sidebar`) e posiziona tab scorrevoli per i filtri, ottimizza padding massicci per favorire i "Tap" col dito tramite media queries `max-width: 768px`.

## 5. `cucina.css`
Interfaccia Kanban (stile Trello). Colora diversamente la griglia tramite i badge (In Attesa: Giallo. Preparazione: Celeste. Pronto: Verde scuro). L'uso prevalente di Flex e display-grid evita scrollate continue in orizzontale al cuoco indaffarato.
