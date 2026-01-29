# Plan de Implementare Detaliat: Membership Player Profile Integration

Acest plan descrie modul în care vom extinde profilul de jucător existent pentru a include secțiuni dinamice configurabile, integrare cu Indeed Membership Pro (UMP) și protecție prin abonamente.

## 1. Configurator de Secțiuni Dinamice (Zona Admin)
Vom crea o pagină de setări în plugin care va permite administratorului să definească structura profilului.
*   **Structură Secțiune**: Fiecare secțiune va avea:
    *   **Titlu Central**: Afișat vizibil în profil.
    *   **Sub-secțiuni/Câmpuri**: O listă de elemente care pot fi adăugate dinamic.
*   **Tipuri de Câmpuri Suportate**:
    *   **Input Text**: Pentru descrieri scurte.
    *   **Textarea/Editor**: Pentru descrieri lungi.
    *   **Select Simplu / Multi-select**: Pentru atribute predefinite.
    *   **Imagine Simplă**: Poza de profil, imagini statice sau **Grafice** (acestea vor fi încărcate ca imagini).
    *   **Galerie de Imagini**: Cu slider interactiv (săgeți stânga-dreapta).
    *   **Video Link**: Câmp pentru link video (YouTube/Vimeo) care va genera automat un **Preview/Embed** în pagina de jucător.
    *   **Upload Fișier (CV)**: Jucătorul va putea încărca un fișier PDF/Doc în secțiunea dedicată (ex: Secțiunea 3 "Player CV").
*   **Mapping UMP**: Fiecare câmp va putea fi legat de un "slug" de câmp custom din Indeed Membership Pro, astfel încât datele completate de jucător în contul său să apară automat în profil.

## 2. Navigare și Elemente Speciale
*   **Navigare Dinamică (Anchor Buttons)**: Vom genera automat un rând de butoane de navigare la începutul profilului. Numărul și etichetele acestor butoane vor corespunde secțiunilor adăugate în Admin. La click, utilizatorul va fi trimis (anchor link) direct la conținutul acelei secțiuni.
*   **Naționalitate și Steag**: Atunci când playerul își selectează naționalitatea în contul său, pagina publică va afișa automat steagul corespunzător lângă nume/detalii.
*   **Previzualizare Video**: Orice link video adăugat de player va fi transformat automat într-un player video integrat.

## 3. Integrarea cu Pagina de Profil a Jucătorului (UMP)
*   **Captare Date**: Jucătorii își vor completa profilul în tab-ul "Profile Details" (UMP Account Page).
*   **Sincronizare SP**: Vom mapă aceste câmpuri către postul de "Player" din SportsPress folosind metadata (`_sp_user_id`).

## 4. Extinderea Template-ului Frontend (SportsPress)
Vom extinde template-ul temei Goalkick (fișierele din `temp_plugins/goalkick-core/goalkick-core/inc/plugins/sportspress/templates/parts/single/`).
*   **Extindere Stil PDF**: Designul va respecta layout-ul din PDF, inclusiv butoanele de navigare pe fundal auriu/negru și organizarea secțiunilor.
*   **Implementare Slider**: Facilităm navigarea printre poze cu sageti stanga dreapta.

## 5. Sistemul de Permisiuni și Membership Access
*   **Restricție Vizualizare**: Vizibilitatea secțiunilor dinamice și a fișierelor (CV) va fi restricționată în funcție de nivelul de membership configurat în plugin.

---

### Confirmare:
Începem acum lucrul la **Interfața Admin** pentru configurarea acestor secțiuni dinamice?
