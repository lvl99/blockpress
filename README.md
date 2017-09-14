# LVL99 ACF Page Builder

v0.2.0

By Matt Scheurich <matt@lvl99.com>

This is currently a proof of concept using [ACF](http://www.advancedcustomfields.com) and its flexible content field
as a way to layout a page's content beyond using the basic text editor.

The main key features is that it should:
  1. Use ACF PRO as is with no modifications or custom extensions; 
  2. Provide an extensible blueprint for creating rich page layouts;
  3. Does not enforce any opinionated or poorly-crafted CSS -- theme developers will have an agnostic and general
     representation of the content to do what they will with the presentation side of things.

I've extensively tried out Visual Composer, Divi Builder, and Gutenberg:
  - Visual Composer is OK, but the shortcode method is clumsy and unreadable. It has bonuses for extensibility, but its
    overuse of the `!important` CSS tag make it unusable for my use case;
  - Same as above for Divi builder, but they've gone bat-sh*t on some of their CSS architecture which makes it
    completely unusable, no matter how nice the frontend/backend editor is (really, it is nice -- and a real shame it's
    unusable in custom themes);
  - Gutenberg is half-baked and even worse they're baking in bad architecture. The HTML comment demarcation is not an
    elegant solution, however the editor UX and IA is mostly nice.

The funny thing is that the customisation that ACF provides with all its supported fields (and ecosystem), and
especially the flexible content field, can be worked into a workable layout system which could have some interesting
applications in both the backend and frontend.
 
While ACF provides a rudimentary backend solution to render the fields for entering and managing data, it could easily
be expanded upon its strong foundation of post meta data to potentially fuel an integrated client-side editor on the
backend and frontend.

One would argue that the `post_content` should be limited to a textual representation of the post's content. This means
things like RSS feeds would show the regular `post_content` when displaying a post's content, and when viewing the post
in the context of the rendered page that it would generate the richer page content and layout. If you think about it,
the `post_content` is like the "plain-text" version of the post's content, kind of like when you do an email campaign
and have a plain-text version.

> ***IMPORTANT:***
>
> As this is still in the prototyping stage its API will most definitely undergo heavy changes.


## Installation

Want to try this experimental plugin out quickly? If you use composer, add this to your `repositories` config:

```json
  {
    "type": "vcs",
    "url": "https://github.com/lvl99/acf-page-builder"
  }
```

You can then `composer require lvl99/acf-page-builder` to integrate it with your project and then enable the plugin
in the WordPress admin area.

Otherwise just download a ZIP from github: https://github.com/lvl99/acf-page-builder/archive/master.zip


## Roadmap

### v0.1.0
  - [x] Basic architecture of generating ACF configuration for layouts.
    - [x] Create ACF configs for layouts (essentially field group that contains a flexible content field)
    - [x] Create ACF configs for blocks (essentially re-usable flexible content layout fields)
      - [x] Ensure generated ACF configs have consistent reproduceable keys (i.e. not random)

### v0.2.0
  - [ ] Figure out how to render layouts easily in templates
    - [x] Add in support for Twig rendering language, just coz I like it
    - [ ] Establish consistent array/object schema format for referring to a block's field values
  - [ ] Create filter for `the_content` which pulls in all the layout meta data to render
  - [ ] Create filter for `the_excerpt` which pulls in all the layout meta data to render with HTML stripped out

### v0.3.0
  - [ ] Create/research REST JSON API to fuel a frontend editor app
  
### v0.4.0
  - [ ] Create saved templates which are a pre-configured layout with blocks for easily creating new pages

### v0.5.0
  - [ ] Investigate existing editors to potentially integrate into frontend editor: CKeditor, maybe?
  - [ ] Build a frontend editor app


## Contributing

Interested in contributing to design or development? You can fork and make pull requests (there's only one branch at the
moment anyway).

I have a private Slack setup too if anyone wants to take part in real-time discussion. You can email <matt@lvl99.com>
for an invite. 


## License

[MIT](LICENSE.md)
