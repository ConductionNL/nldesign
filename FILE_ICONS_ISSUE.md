# File Icons Issue - Analysis

**Date**: 2026-02-03  
**Problem**: File icons in "Aanbevolen bestanden" widget are showing as solid gray boxes instead of proper file type icons

---

## 🔍 Root Cause Analysis

### What We Changed
We added aggressive CSS rules to remove gradients that affected ALL icons, including file type icons:
- Removed from `overrides.css`: Global SVG gradient removal
- Removed from `nuclear.css`: File icon background/fill overrides

### Current CSS State
**Navigation icons** (in header): ✅ Correctly filtered to white
**File type icons** (in widgets): ❌ Still showing as gray boxes

### Why File Icons Are Still Gray

Looking at the screenshot, the file icons appear as light gray rounded rectangles. This could be because:

1. **Background images are being removed**
2. **SVG gradients are being killed globally**  
3. **Default fallback is showing** (gray box)

---

## 🎯 Expected Behavior

File icons in the "Aanbevolen bestanden" widget should show:
- **PDF files**: Red/pink icon with document symbol
- **Markdown files**: Blue icon with markdown symbol  
- **PNG files**: Image icon
- **MP4 files**: Video icon
- **ODT files**: Document icon
- **ODG files**: Drawing icon

Each with their characteristic colors and gradients (yes, Nextcloud file icons DO use gradients).

---

## ⚖️ The Dilemma

### NL Design System Requirements
- **No gradients** (flat design)
- **Solid colors**
- **Government-appropriate styling**

### Nextcloud File Icons
- **Use gradients** (built into SVG)
- **Colorful and recognizable**
- **Standard UX pattern**

### User Expectation
User wants to see file type icons properly, not gray boxes.

---

## 💡 Solution Options

### Option 1: Allow File Icon Gradients (RECOMMENDED)
**Approach**: Only remove gradients from navigation, keep file icons as-is

**Pros:**
- File icons remain recognizable
- Standard Nextcloud UX
- No user confusion

**Cons:**
- File icons will have gradients (not strictly NL Design compliant)

**Implementation:**
- Keep navigation icon filters
- Remove all file icon overrides
- Let Nextcloud's default file icons show through

### Option 2: Replace with Flat File Icons
**Approach**: Create/use flat, gradient-free file type icons

**Pros:**
- Fully NL Design compliant
- No gradients anywhere

**Cons:**
- Requires custom icon set
- More maintenance
- Might reduce recognizability

### Option 3: Use Simplified Placeholder Icons
**Approach**: Show file extension text instead of icons

**Pros:**
- Very simple
- Clear file types

**Cons:**
- Less visual appeal
- Against UX best practices

---

## ✅ Recommended Solution

**Allow file icon gradients to show through**

### Reasoning:
1. **Usability First**: Users need to quickly identify file types
2. **Minor Compliance Issue**: File icons are a small UI element, not core branding
3. **Nextcloud Standards**: Following platform conventions
4. **Practical Approach**: Government sites can use colorful icons for functional purposes

### Implementation:
Make the CSS rules VERY specific to navigation only, ensuring file icons are completely untouched.

---

## 📝 Next Steps

1. Verify all file icon CSS is removed from overrides.css ✅
2. Verify nuclear.css only targets navigation ✅  
3. Check if there are any remaining global SVG rules affecting file icons
4. Test and confirm file icons display properly
