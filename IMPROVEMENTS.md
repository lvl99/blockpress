# Improvements

A list of things that I need to refactor/improve:

  - ~~Architecture of Layouts/Blocks instances saved in Builder class and their generated ACF configs. I want to be able
    to know which generated config comes from what block and layout.~~
  - ~~Maybe the same is needed for all the fields also stored to the Builder in the flatmap~~
  - Next after above is to show chain of nested blocks in flatmap...
  - Maybe also save index of row in layout/nested block field?
  - Some of the API with view rendering needs to be refactored
  - ~~Maybe adding Twig wasn't such a good idea. No way for i18n stuff and makes it harder for using WP functions. As much
    as I loathe the global functions, they are kind of necessary in the template phase.~~
  - Rendering blocks/layouts has been a bit piecemeal -- will need serious refactor
  - I used this `$options` object as a way to pass through common data shared between various functions in classes and
    outside, but it's getting confusing. There must be a better way...
  - You know, I've just realised that layouts are just exactly like blocks -- they should support extra fields too!
  - A lot of the `$options` got a bit mucked up (things like `layout` can be layout instance or a layout name or
    layout slug). Need to really standardise that to make it clear and consistent.
  - Figure out how to standardise nested block parent references for easy rendering and caching retrieval.
  - ~~Rename plugin to avoid reminders about upgrading due to existing plugin with same name~~
  - Refactor all layout/block classes to define content/customise/configure fields in `__construct` function to support
    i18n/i10n functions (can also mean to remove $pecial $auce too -- that was a silly idea to begin with)
