---
name: Diário Gastronômico
colors:
  surface: '#fbf9f6'
  surface-dim: '#dbdad7'
  surface-bright: '#fbf9f6'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f5f3f0'
  surface-container: '#efeeeb'
  surface-container-high: '#eae8e5'
  surface-container-highest: '#e4e2df'
  on-surface: '#1b1c1a'
  on-surface-variant: '#56423e'
  inverse-surface: '#30312f'
  inverse-on-surface: '#f2f0ed'
  outline: '#89726d'
  outline-variant: '#dcc1bb'
  surface-tint: '#9c432f'
  primary: '#99402d'
  on-primary: '#ffffff'
  primary-container: '#b85842'
  on-primary-container: '#fffbff'
  inverse-primary: '#ffb4a4'
  secondary: '#695b58'
  on-secondary: '#ffffff'
  secondary-container: '#f1deda'
  on-secondary-container: '#6f615e'
  tertiary: '#7a5244'
  on-tertiary: '#ffffff'
  tertiary-container: '#966a5b'
  on-tertiary-container: '#fffbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ffdad2'
  primary-fixed-dim: '#ffb4a4'
  on-primary-fixed: '#3d0600'
  on-primary-fixed-variant: '#7d2c1a'
  secondary-fixed: '#f1deda'
  secondary-fixed-dim: '#d5c3be'
  on-secondary-fixed: '#231917'
  on-secondary-fixed-variant: '#504441'
  tertiary-fixed: '#ffdbcf'
  tertiary-fixed-dim: '#efbba9'
  on-tertiary-fixed: '#301409'
  on-tertiary-fixed-variant: '#623e30'
  background: '#fbf9f6'
  on-background: '#1b1c1a'
  surface-variant: '#e4e2df'
typography:
  display:
    fontFamily: Playfair Display
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  h1:
    fontFamily: Playfair Display
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
  h1-mobile:
    fontFamily: Playfair Display
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
  h2:
    fontFamily: Playfair Display
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  h3:
    fontFamily: Playfair Display
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  h4:
    fontFamily: Playfair Display
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: DM Sans
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body:
    fontFamily: DM Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: DM Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label:
    fontFamily: DM Sans
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.05em
  caption:
    fontFamily: DM Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 16px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  2xl: 48px
  3xl: 64px
---

## Brand & Style
The brand personality is intimate, warm, and curated. It serves as a digital heirloom for couples, transforming functional restaurant logging into an emotional memory-keeping experience. 

The design style is **Modern Corporate with a Tactile twist**, leaning heavily into editorial layouts. It prioritizes whitespace and high-quality food photography, using a "Digital Scrapbook" metaphor. Surfaces should feel soft and inviting, avoiding the coldness of traditional utility apps in favor of an aesthetic that feels like a physical linen-bound journal.

## Colors
The palette is rooted in organic, culinary tones. 
- **Terracota (#D16B54)** drives the primary actions, evoking warmth and appetite. 
- **Marrom profundo (#4A3E3B)** provides grounding and sophistication for secondary navigation.
- **Creme (#FDFBF8)** and **Surface Secondary (#F7F2EE)** replace harsh whites and greys to maintain an "off-white paper" feel.
- Backgrounds should primarily use the Creme base to reduce eye strain and increase the "intimate" feel of the UI.

## Typography
This system uses a high-contrast typographic pairing. **Playfair Display** provides an authoritative, editorial elegance for titles and storytelling moments. **DM Sans** is used for all functional and body text to ensure maximum legibility and a modern, clean feel. 

For the "Memory" aspect of the app, use `body-lg` for personal notes and journal entries. Use `label` in uppercase with letter spacing for category tags and metadata to create a clear visual hierarchy against the serif headlines.

## Layout & Spacing
The layout follows a **Fluid Grid** model with generous margins to evoke a sense of calm. 

- **Mobile:** Use a 4-column grid with 20px outside margins. 
- **Desktop:** Use a 12-column centered grid with a maximum width of 1200px.
- **Vertical Rhythm:** Use the `lg` (24px) unit for spacing between distinct content sections and `md` (16px) for internal component spacing. 

Avoid packing elements tightly; white space is a functional tool here to emphasize the emotional quality of the photography.

## Elevation & Depth
Depth is created through **Tonal Layers** and **Ambient Shadows**. 

- **Level 0 (Background):** #FDFBF8 (Creme).
- **Level 1 (Cards/Surfaces):** #FFFFFF (White) with a very soft, diffused shadow: `0px 4px 20px rgba(74, 62, 59, 0.05)`.
- **Level 2 (Floating Elements/Modals):** #FFFFFF with a more pronounced but still soft shadow: `0px 10px 30px rgba(74, 62, 59, 0.08)`.

Avoid heavy borders; use #E5DED9 (Border) only when necessary for structural definition on input fields or dividers.

## Shapes
The shape language is "Soft-Modern." 
- **Buttons & Small Components:** Use 8px (0.5rem) corner radius.
- **Cards & Large Containers:** Use 16px (1rem) corner radius to create a friendlier, "album" feel.
- **Inputs:** Use 8px (0.5rem) to maintain consistency with buttons.
- **Rating Indicators:** Circular or highly rounded elements to feel approachable.

## Components

### Buttons
- **Primary:** Terracota (#D16B54) background, White text. 8px radius.
- **Secondary:** Surface Secondary (#F7F2EE) background, Text Primary (#2D2422) text.
- **Ghost:** No background, Terracota text. Use for less critical actions.

### Cards (The "Memory" Card)
Cards should feature a prominent image placeholder at the top (aspect ratio 4:3 or 1:1). 16px padding. Titles in Playfair Display. The card footer should include a subtle date and the rating indicator.

### Evaluation Indicators (Scale 0-10)
A horizontal bar or a series of dots. The active state uses Terracota (#D16B54), while the inactive state uses Border (#E5DED9). Use a slightly larger indicator for the current selection during the "Record" flow.

### Bottom Navigation
A clean, Marrom profundo (#4A3E3B) or White background with 24px icons. Active state uses Terracota. Labels in `Caption` size.

### Input Fields
Search and form inputs should use the Surface color (#FFFFFF) with an 8px radius and a 1px border (#E5DED9). On focus, the border shifts to Terracota.

### Chips/Tags
Used for "Cuisine Type" or "Vibe." Use Primary Light (#F9EBE7) background with Terracota text for a soft, integrated look.