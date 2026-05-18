// Configuración de Tailwind CSS para la versión 2 de la página.
// Se ha separado en un archivo JavaScript para mantener el HTML limpio
// y permitir una mejor mantenibilidad y posible reutilización o 
// caché por parte del navegador.
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      "colors": {
              "background": "#fbf9f9",
              "on-error-container": "#93000a",
              "inverse-primary": "#c6c6c6",
              "on-tertiary-fixed": "#1b1b1b",
              "tertiary-fixed-dim": "#c6c6c6",
              "tertiary-fixed": "#e2e2e2",
              "on-secondary-fixed-variant": "#930010",
              "surface-container-low": "#f5f3f3",
              "secondary": "#b6171e",
              "error": "#ba1a1a",
              "on-primary-container": "#848484",
              "surface-variant": "#e3e2e2",
              "on-error": "#ffffff",
              "primary": "#000000",
              "on-secondary-container": "#fffbff",
              "on-tertiary": "#ffffff",
              "secondary-container": "#da3433",
              "inverse-on-surface": "#f2f0f0",
              "surface-container-lowest": "#ffffff",
              "surface-dim": "#dbdad9",
              "error-container": "#ffdad6",
              "primary-container": "#1b1b1b",
              "on-surface-variant": "#4c4546",
              "secondary-fixed-dim": "#ffb3ac",
              "secondary-fixed": "#ffdad6",
              "on-primary": "#ffffff",
              "primary-fixed-dim": "#c6c6c6",
              "primary-fixed": "#e2e2e2",
              "on-secondary": "#ffffff",
              "surface-container-highest": "#e3e2e2",
              "surface-container-high": "#e9e8e7",
              "surface-bright": "#fbf9f9",
              "on-tertiary-fixed-variant": "#474747",
              "on-secondary-fixed": "#410003",
              "outline-variant": "#cfc4c5",
              "surface": "#fbf9f9",
              "tertiary": "#000000",
              "on-primary-fixed": "#1b1b1b",
              "on-primary-fixed-variant": "#474747",
              "outline": "#7e7576",
              "inverse-surface": "#303031",
              "on-surface": "#1b1c1c",
              "tertiary-container": "#1b1b1b",
              "surface-container": "#efeded",
              "on-tertiary-container": "#848484",
              "on-background": "#1b1c1c",
              "surface-tint": "#5e5e5e"
      },
      "borderRadius": {
              "DEFAULT": "0.125rem",
              "lg": "0.25rem",
              "xl": "0.5rem",
              "full": "0.75rem"
      },
      "spacing": {
              "unit": "8px",
              "margin-mobile": "20px",
              "container-max": "1280px",
              "gutter": "24px",
              "margin-desktop": "64px"
      },
      "fontFamily": {
              "headline-sm": ["EB Garamond"],
              "label-caps": ["Hanken Grotesk"],
              "body-md": ["Hanken Grotesk"],
              "display-lg-mobile": ["EB Garamond"],
              "headline-md": ["EB Garamond"],
              "display-lg": ["EB Garamond"],
              "body-lg": ["Hanken Grotesk"]
      },
      "fontSize": {
              "headline-sm": ["24px", {"lineHeight": "1.3", "fontWeight": "500"}],
              "label-caps": ["12px", {"lineHeight": "1", "letterSpacing": "0.1em", "fontWeight": "700"}],
              "body-md": ["16px", {"lineHeight": "1.5", "fontWeight": "400"}],
              "display-lg-mobile": ["36px", {"lineHeight": "1.1", "fontWeight": "600"}],
              "headline-md": ["32px", {"lineHeight": "1.2", "fontWeight": "500"}],
              "display-lg": ["48px", {"lineHeight": "1.1", "letterSpacing": "-0.02em", "fontWeight": "600"}],
              "body-lg": ["18px", {"lineHeight": "1.6", "fontWeight": "400"}]
      }
    },
  },
}
