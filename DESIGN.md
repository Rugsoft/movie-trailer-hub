---
version: alpha
name: Movie Trailer Hub
colors:
  primary: "#f59e0b" # Amber / Cinematic Accent
  secondary: "#dc2626" # Crimson / Active and Alert Actions
  background: "#081425" # Deep Space Dark Blue
  surface: "#152031" # Elevated Surface Card
  surface-elevated: "#1f2a3c" # High Elevation Surface
  text-primary: "#d8e3fb" # Soft Ice White
  text-muted: "#a08e7a" # Warm Sandy Gold / Secondary Text
  border: "rgba(216, 195, 173, 0.1)" # Subtly warm border
typography:
  fontFamilyHeadline: "'Montserrat', sans-serif"
  fontFamilyBody: "'Inter', sans-serif"
  fontFamilyBrand: "'Bebas Neue', sans-serif"
  h1:
    fontSize: "2.5rem"
    fontWeight: 800
  h2:
    fontSize: "1.75rem"
    fontWeight: 700
  h3:
    fontSize: "1.25rem"
    fontWeight: 600
  body:
    fontSize: "1rem"
    fontWeight: 400
rounded:
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "24px"
---

# Movie Trailer Hub - Sistema de Diseño (DESIGN.md)

Este documento define la identidad visual, las directrices estéticas y los tokens de diseño para la plataforma **Movie Trailer Hub**. El objetivo es proporcionar una fuente única de verdad para mantener la coherencia cinematográfica de la interfaz ante cualquier desarrollo de código asistido por IA.

---

## 🎭 Filosofía de Diseño: Cinematic Modern Dark
El estilo visual de **Movie Trailer Hub** está inspirado en las plataformas de streaming premium contemporáneas. Utiliza un tema oscuro profundo ("Deep Space Blue") complementado con destellos cálidos de luz ámbar (`primary`) y alertas sutiles carmesí (`secondary`), evocando el ambiente de una sala de cine clásica a oscuras.

---

## 🎨 Paleta de Colores
*   **Fondo Base (`background`):** `#081425`. Debe usarse para el fondo general de la aplicación.
*   **Superficie (`surface`):** `#152031`. Para contenedores de contenido, tarjetas de películas e interfaces secundarias.
*   **Superficie Elevada (`surface-elevated`):** `#1f2a3c`. Para modales, dropdowns y elementos activos que requieren un contraste adicional sobre la superficie base.
*   **Primario / Ámbar (`primary`):** `#f59e0b`. Reservado para elementos interactivos principales (botones de acción principales, estrellas de valoración activas, llamadas a la acción críticas).
*   **Secundario / Carmesí (`secondary`):** `#dc2626`. Utilizado para estados de alerta, botones de eliminación, marcas especiales e indicadores que requieran atención inmediata.
*   **Texto Principal (`text-primary`):** `#d8e3fb`. Alto contraste accesible sobre los fondos oscuros.
*   **Texto Muted (`text-muted`):** `#a08e7a`. Tono dorado arenoso para metadatos, subtítulos y descripciones secundarias.

---

## ✍️ Tipografía
*   **Títulos principales y destacados:** Se utiliza **Montserrat** con grosores semi-bold y bold para conferir un aspecto estructurado y premium.
*   **Textos descriptivos y formularios:** Se utiliza **Inter** para garantizar una legibilidad excepcional en pantallas de cualquier tamaño.
*   **Logotipo e identidad corporativa:** Se emplea **Bebas Neue** por su estética condensada que emula los carteles cinematográficos clásicos.

---

## 📐 Bordes y Espaciados
*   **Bordes generales:** Mantener bordes ultra sutiles utilizando tonos translúcidos templados en lugar de colores planos: `rgba(216, 195, 173, 0.1)`.
*   **Esquinas redondeadas:**
    *   `radius-sm` (`8px`): Para inputs, botones y selectores pequeños.
    *   `radius-md` (`12px`): Para tarjetas de trailers, listas y contenedores.
    *   `radius-lg` (`16px`): Para secciones grandes e imágenes de posters destacados.
    *   `radius-xl` (`24px`): Reservado para badges interactivos y elementos decorativos curvos.

---

## 🛡️ Directrices de Componentes (Do's & Don'ts)

### ✅ Qué Hacer (Do's)
*   **Efecto Vidrio (Glassmorphism):** Utiliza fondos semitransparentes combinados con desenfoques `backdrop-filter: blur(10px)` para dar profundidad a las tarjetas elevadas.
*   **Glow sutil:** Aplica sombras con brillos de colores translúcidos (`--primary-glow`) únicamente cuando un elemento de llamada a la acción reciba el foco o estado hover.
*   **Transiciones Suaves:** Todos los botones, enlaces e inputs deben contar con la transición fluida definida en el tema: `all 0.3s cubic-bezier(0.4, 0, 0.2, 1)`.

### ❌ Qué Evitar (Don'ts)
*   **No usar colores sólidos planos:** Evita usar blanco puro (`#fff`) o negro puro (`#000`) para componentes de interfaz. Utiliza siempre las variables semánticas del tema.
*   **No sobrecargar con sombras:** La elevación se debe conseguir mediante el contraste de color (`--bg-surface` vs `--bg-surface-elevated`) y bordes finos, no mediante sombras pesadas o difusas.
*   **Sin esquinas rectas:** No se deben generar botones o cajas con bordes de 90 grados; todos deben respetar la jerarquía de bordes redondeados.
