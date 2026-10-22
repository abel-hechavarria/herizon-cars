# HCars Process

Widget nativo de Elementor: `herizon-process`, categoría **Herizon Cars**. Presenta una sección con imagen de fondo, textos y CTA, más una lista de pasos con imágenes y tarjetas diagonales.

## Insertar y configurar

1. Guarda los cambios pendientes y recarga Elementor si estaba abierto antes de instalar esta versión del theme.
2. Busca **HCars Process** y arrástralo a un contenedor. Para una composición como la referencia, usa un contenedor de ancho completo, sin padding adicional; el widget tiene sus propios espaciados.
3. En **Contenido / Tartalom**, configura etiqueta superior, título, descripción, imagen de fondo y posición. El título admite saltos de línea y una etiqueta HTML elegible.
4. En **Pasos / Lépések**, añade, elimina, duplica o reordena tarjetas. Cada una tiene número, título, descripción, imagen, posición y enlace opcional. Deja el número vacío para calcularlo según el orden. Los colores individuales prevalecen sobre los generales.
5. Configura texto, enlace/ancla, variante e icono del botón. Puedes ocultar el botón, su icono o el círculo del icono. El icono inicial de reproducción funciona como decoración de un enlace normal.

Las imágenes se eligen desde la biblioteca de medios. No se incluyen fotografías del cliente en el código. Una imagen eliminada deja el texto disponible. Una tarjeta sin texto no crea un enlace vacío.

## Diseño

| Grupo | Opciones |
|---|---|
| Disposición | Columnas separadas o apiladas; proporción de la zona de texto; ancho máximo del texto; altura mínima; espaciados y padding. |
| Fondo | Color del bloque, imagen y posición, ancho de la imagen, fondo complementario y overlay sólido o degradado. |
| Tarjetas | Columnas, separación, altura mínima, padding, corte diagonal, radio, borde, sombra, fondo y overlay. |
| Texto | Tipografía y color independientes para etiqueta, título, descripción, número y textos de los pasos. |
| Botón normal/hover | Contrato HCars Button: colores y degradados, tipografía, padding por lado, ancho/alineación, borde/sombra e icono/círculo. |

Los controles responsive usan los breakpoints activos de Elementor. Los valores iniciales de este sitio son escritorio por encima de 1024 px, tablet hasta 1024 px y móvil hasta 767 px. En tablet el texto y las tarjetas se apilan; en móvil las tarjetas pasan a una columna y el corte diagonal a cero. Estos valores se pueden editar.

Las fuentes del kit se conservan como respaldo. Puedes elegir una fuente serif en la tipografía del título para aproximar la referencia. El nombre de una fuente no instala un archivo de fuente nuevo.

## Contrato frontend

- HTML servido por PHP: `section.hc-process`, capas decorativas de fondo/overlay, `.hc-process__inner`, `.hc-process__content`, `ol.hc-process__steps` y `li.hc-process__step` por tarjeta.
- Cada tarjeta usa `.hc-process__card`, `.hc-process__media`, `.hc-process__image`, `.hc-process__card-overlay` y `.hc-process__card-content`. Los títulos/números/descripciones tienen clases propias.
- El CTA usa el renderizador compartido `herizon_cars_button_render()` dentro de `.hc-process__cta`.
- Estilo aislado: `src/scss/process.scss`, handle `herizon-cars-process`, hash resuelto mediante manifest. También depende del CSS de HCars Button. No añade JavaScript de frontend.
- Variables principales: `--hc-process-copy-width`, `--hc-process-copy-max-width`, `--hc-process-min-height`, `--hc-process-gap`, `--hc-process-columns`, `--hc-process-gap-cards`, `--hc-process-card-min-height`, `--hc-process-card-padding` y `--hc-process-cut`.
- Los selectores de repetidor incluyen wrapper e ID del elemento para aislar los estilos entre instancias.
- Los textos se escapan; las etiquetas de título se validan; los enlaces usan protocolos permitidos. Los iconos SVG de medios pasan por el saneamiento compartido.
- Los enlaces tienen foco visible. El contenido no depende de JavaScript; el CTA respeta movimiento reducido mediante sus estilos compartidos. El orden de lectura sigue el DOM.

Para modificar estilos, edita SCSS y ejecuta `pnpm build`. Para regenerar traducciones húngaras: `python scripts/process-translations.py`.

Esta funcionalidad se entrega localmente. Crear una release o instalar actualizaciones en producción es un paso independiente.
