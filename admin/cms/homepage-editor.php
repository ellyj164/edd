<?php
/**
 * Homepage CMS Editor - Final Stable Version 4
 * Using the most robust method for file includes to finally resolve the "undefined function" error.
 */

// --- CRITICAL INCLUDES (Final, Most Reliable Method) ---
// This calculates the project root from the current file's location.
// It does not depend on server configuration and will work correctly.
$project_root = dirname(__DIR__, 2); // Navigates up two directories from /admin/cms to the project root.
require_once $project_root . '/includes/auth.php';
require_once $project_root . '/includes/db.php';
require_once $project_root . '/includes/csrf.php';
require_once $project_root . '/includes/rbac.php';


// --- PHP Error Handling & Security ---
ini_set('display_errors', 0);
error_reporting(E_ALL);

$pdo = db();
requireAdminAuth();
checkPermission('cms.manage');

// --- AJAX Request Handler ---
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'error' => 'An unknown error occurred.'];

    try {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid session token. Please refresh the page.');
        }

        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'save_layout':
                $sections = json_decode($_POST['sections'] ?? '[]', true);
                if (!is_array($sections)) throw new Exception('Invalid sections data.');
                $stmt = $pdo->prepare("INSERT INTO homepage_sections (section_key, section_data, created_at, updated_at) VALUES ('layout_config', ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE section_data = VALUES(section_data), updated_at = NOW()");
                $stmt->execute([json_encode($sections)]);
                $response = ['success' => true, 'message' => 'Layout saved.'];
                break;

            case 'get_section_data':
                $sectionId = $_POST['section_id'] ?? '';
                if (empty($sectionId)) throw new Exception('Section ID is required.');
                $stmt = $pdo->prepare("SELECT section_data FROM homepage_sections WHERE section_key = ?");
                $stmt->execute([$sectionId]);
                $data = $stmt->fetchColumn();
                $response = ['success' => true, 'data' => $data ? json_decode($data, true) : null];
                break;

            case 'save_section_data':
                $sectionId = $_POST['section_id'] ?? '';
                if (empty($sectionId)) throw new Exception('Section ID is required.');

                $stmt = $pdo->prepare("SELECT section_data FROM homepage_sections WHERE section_key = ?");
                $stmt->execute([$sectionId]);
                $sectionData = json_decode($stmt->fetchColumn() ?: '{}', true);

                foreach (['title', 'subtitle', 'button_text', 'button_link', 'content'] as $field) {
                    if (isset($_POST[$field])) $sectionData[$field] = $_POST[$field];
                }

                if ($sectionId === 'hero_banner' && isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['background_image'];
                    if ($file['size'] > 5 * 1024 * 1024) throw new Exception('File size exceeds 5MB.');
                    
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($file['tmp_name']);
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($mimeType, $allowedMimes)) throw new Exception('Invalid file type.');

                    $uploadDir = $project_root . '/uploads/hero_images/';
                    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) throw new Exception('Cannot create upload directory.');

                    if (!empty($sectionData['background_image']) && file_exists($project_root . '/' . $sectionData['background_image'])) {
                        @unlink($project_root . '/' . $sectionData['background_image']);
                    }
                    
                    $uniqueFilename = 'hero_' . time() . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $uniqueFilename)) throw new Exception('Failed to save uploaded file.');
                    
                    $sectionData['background_image'] = 'uploads/hero_images/' . $uniqueFilename;
                }

                $stmt = $pdo->prepare("INSERT INTO homepage_sections (section_key, section_data, created_at, updated_at) VALUES (?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE section_data = VALUES(section_data), updated_at = NOW()");
                $stmt->execute([$sectionId, json_encode($sectionData)]);
                
                $response = ['success' => true, 'message' => 'Section saved.', 'data' => $sectionData];
                break;

            default:
                throw new Exception('Invalid action specified.');
        }
    } catch (Throwable $e) {
        http_response_code(500);
        $response['error'] = 'Server Error: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// --- Initial Page Load Data ---
$stmt = $pdo->query("SELECT section_data FROM homepage_sections WHERE section_key = 'layout_config'");
$sections = json_decode($stmt->fetchColumn() ?: '[]', true);
if (empty($sections)) {
    $sections = [
        ['id' => 'hero_banner', 'type' => 'hero', 'title' => 'Hero Banner'],
        ['id' => 'featured_categories', 'type' => 'categories', 'title' => 'Featured Categories'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage Editor</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; } .editor-container { display: flex; gap: 1.5rem; } .sidebar { width: 350px; flex-shrink: 0; background: #fff; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); } .preview { flex-grow: 1; background: #fff; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); } .section-card { display: flex; align-items: center; justify-content: space-between; border: 1px solid #e9ecef; padding: 0.75rem 1rem; margin-bottom: 0.5rem; border-radius: 5px; background: #fff; cursor: grab; } #homepage-preview .preview-section { border: 1px solid #dee2e6; border-radius: 5px; margin-bottom: 1rem; padding: 1.5rem; } #homepage-preview .preview-section h5 { font-size: 1rem; color: #868e96; text-transform: uppercase; margin-bottom: 1rem; } .preview-hero { text-align: center; color: white; padding: 3rem 1.5rem !important; background-size: cover; background-position: center; background-color: #343a40; text-shadow: 0 1px 3px rgba(0,0,0,0.4); border-radius: 5px; } .preview-hero h1 { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <h1 class="h3 mb-4">Homepage Editor</h1>
        <div class="editor-container">
            <div class="sidebar">
                <h5 class="mb-3">Layout Sections</h5>
                <div id="section-list">
                    <?php foreach ($sections as $section): ?>
                        <div class="section-card" data-id="<?= htmlspecialchars($section['id']) ?>" data-type="<?= htmlspecialchars($section['type']) ?>">
                            <strong><?= htmlspecialchars($section['title']) ?></strong>
                            <button class="btn btn-sm btn-outline-primary btn-edit">Edit</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-primary mt-3 w-100" id="save-layout">Save Layout</button>
            </div>
            <div class="preview">
                <h5 class="mb-3">Live Homepage Preview</h5>
                <div id="homepage-preview"></div>
            </div>
        </div>
    </div>

    <!-- Modal HTML -->
    <div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="edit-modal-title">Edit Section</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><form id="edit-form" enctype="multipart/form-data"><input type="hidden" name="section_id" id="edit-section-id"><input type="hidden" name="action" value="save_section_data"><input type="hidden" name="csrf_token" id="edit-csrf-token"><div id="edit-fields"></div></form></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button><button type="button" class="btn btn-primary" id="save-section">Save Changes</button></div></div></div></div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs/Sortable.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        const csrfToken = '<?= generateCSRFToken() ?>';
        new Sortable(document.getElementById('section-list'), { animation: 150 });

        const getFieldHtml = (field) => {
            const inputClass = field.type === 'file' ? 'form-control-file' : 'form-control';
            const valueAttr = field.type !== 'file' ? `value="${field.value || ''}"` : '';
            return `<div class="form-group"><label>${field.label}</label><input type="${field.type}" class="${inputClass}" name="${field.name}" ${valueAttr} placeholder="${field.placeholder || ''}"></div>`;
        };
        const sectionDefinitions = {
            hero: {
                fields: (data = {}) => [ { name: 'title', label: 'Title', type: 'text', value: data.title }, { name: 'subtitle', label: 'Subtitle', type: 'text', value: data.subtitle }, { name: 'button_text', label: 'Button Text', type: 'text', value: data.button_text }, { name: 'button_link', label: 'Button Link', type: 'text', value: data.button_link }, { name: 'background_image', label: `Background Image ${data.background_image ? `(<a href="/${data.background_image}" target="_blank">current</a>)` : ''}`, type: 'file' } ],
                render: (data = {}, title) => { const style = data.background_image ? `background-image: url('/${data.background_image}?t=${new Date().getTime()}');` : ''; return `<div class="preview-section preview-hero" style="${style}"><h5>${title}</h5><h1>${data.title || 'Welcome'}</h1><p>${data.subtitle || 'Subtitle'}</p><a href="${data.button_link || '#'}" class="btn btn-light">${data.button_text || 'Button'}</a></div>`; }
            },
            categories: {
                fields: (data = {}) => [{ name: 'content', label: 'Content', type: 'textarea', value: data.content }],
                render: (data = {}, title) => `<div class="preview-section"><h5>${title}</h5><div>${(data.content || 'No content set.')}</div></div>`
            },
            default: {
                fields: (data = {}) => [],
                render: (data = {}, title) => `<div class="preview-section"><h5>${title}</h5><p>This section is for display only.</p></div>`
            }
        };
        function renderPreview() {
            const previewContainer = $('#homepage-preview').html('<div class="text-center p-4">Loading preview...</div>');
            const sections = Array.from($('#section-list .section-card')).map(el => ({ id: $(el).data('id'), type: $(el).data('type'), title: $(el).find('strong').text() }));
            const promises = sections.map(s => $.post('', { action: 'get_section_data', section_id: s.id, csrf_token: csrfToken }));
            Promise.all(promises).then(results => {
                previewContainer.empty();
                results.forEach((res, i) => {
                    const section = sections[i];
                    if (!res || !res.success) { previewContainer.append(`<div class="alert alert-warning">Could not load section: ${section.title}</div>`); return; }
                    const sectionDef = sectionDefinitions[section.type] || sectionDefinitions.default;
                    const html = sectionDef.render(res.data, section.title);
                    previewContainer.append($(html).attr('data-preview-id', section.id));
                });
            }).catch(error => {
                const errorMsg = error.responseJSON ? error.responseJSON.error : "A server error occurred.";
                previewContainer.html(`<div class="alert alert-danger"><strong>Error:</strong> Failed to load homepage preview. ${errorMsg}</div>`);
            });
        }
        $(document).on('click', '.btn-edit', function() {
            const card = $(this).closest('.section-card'), sectionId = card.data('id'), sectionType = card.data('type');
            const sectionDef = sectionDefinitions[sectionType] || sectionDefinitions.default;
            $('#edit-modal-title').text(`Edit: ${card.find('strong').text()}`);
            $('#edit-section-id').val(sectionId);
            $('#edit-csrf-token').val(csrfToken);
            const fieldsContainer = $('#edit-fields').html('<p class="text-center">Loading...</p>');
            $.post('', { action: 'get_section_data', section_id: sectionId, csrf_token: csrfToken }).done(res => {
                fieldsContainer.empty();
                if (res.success) { sectionDef.fields(res.data).forEach(field => fieldsContainer.append(getFieldHtml(field))); } 
                else { fieldsContainer.html(`<div class="alert alert-danger">${res.error || 'Could not load data.'}</div>`); }
            });
            $('#editModal').modal('show');
        });
        $('#save-section').on('click', function() {
            const form = document.getElementById('edit-form'), formData = new FormData(form);
            $(this).prop('disabled', true).text('Saving...');
            $.ajax({ url: '', type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json' })
            .done(res => { if (res.success) { $('#editModal').modal('hide'); renderPreview(); } else { alert(`Error: ${res.error}`); } })
            .fail(() => alert('Failed to save section. A server error occurred.'))
            .always(() => $(this).prop('disabled', false).text('Save Changes'));
        });
        $('#save-layout').on('click', function() {
            const sections = Array.from($('#section-list .section-card')).map(el => ({ id: $(el).data('id'), type: $(el).data('type'), title: $(el).find('strong').text() }));
            $(this).prop('disabled', true).text('Saving...');
            $.post('', { action: 'save_layout', sections: JSON.stringify(sections), csrf_token: csrfToken })
            .done(res => { if(res.success) alert(res.message); else alert(`Error: ${res.error}`); })
            .fail(() => alert('Failed to save layout.'))
            .always(() => $(this).prop('disabled', false).text('Save Layout'));
        });
        renderPreview();
    });
    </script>
</body>
</html>