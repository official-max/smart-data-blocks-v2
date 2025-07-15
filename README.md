# smart-data-blocks-v2
A simple ACF-style repeater plugin for custom admin fields.

== Usage ==

### 1. Creating Field Groups:
- Go to **Smart Blocks → Add Group**
- Add fields inside each group (supports nested repeaters)

### 2. Location Rules:
Assign field groups to:
- All posts of a specific type  
- A specific post/page  
- Pages using a specific template  

### 3. Access Field Values in Theme (Frontend):

```php
// Get a single field
$value = sdb_get_field('group_slug', 'field_name');

// Get all fields in a group
$data = sdb_get_field('group_slug');

// Get field for a specific post (WIP)
$data = sdb_get_field('group_slug', 'field_name', $post_id);
