###How to Configure Multibanner

1. Modules: Require multibanner module, multibanner_default module, inline_entity_form module, and unslider library in project composer.json
2. Install the modules.
3. Set "multibanner" entity permissions
4. Create multibanner entity types if not already present.
5. Add field_multibanner to desired node type, and leave field setting defaults, so that:
  a) "Required field" is left unchecked (although it CAN be checked)
  b) "Reference method" is "default"
  c) "Create referenced entities if they don't already exist" is unchecked
  d) Check appropriate multibanner entity types to include
  e) "Sort by" is empty
6. Optionally: add field_multibanner_layout to desired node type
  a) "Required field" is checked
  b) "Default value" is set to "Default"
7. Set node form display so that:
  a) field_multibanners is a "Inline entity form - Complex" that:
    i) Form mode is "default"
    ii) "Allow users to add new banner entities" is checked
    iii) "Allow users to add existing banner entities" is checked
    iv) "Autocomplete matching" may be anything
8. Set node view display(s) so that:
  a) field_multibanner and field_multibanner_layout (if present) are completely hidden
9. Set multibanner blocks to desired region(s)
  a) Visiblity settings are optional

Note: Focal point module is not installed. To use it, require and enable focal point. Then create and image style using focal point, and then under "Admin > Structure > Multibanners > Some type > Manage Display" set the appropriate image field to use the focal point image style image formatter.