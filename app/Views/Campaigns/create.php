<style>
    .template-picker {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .template-card {
        width: 160px;
        border: 2px solid #e3e6f0;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
        overflow: hidden;
        background: #fff;
    }
    .template-card:hover {
        border-color: #4e73df;
        box-shadow: 0 2px 8px rgba(78,115,223,0.15);
    }
    .template-card.selected {
        border-color: #4e73df;
        box-shadow: 0 0 0 3px rgba(78,115,223,0.25);
    }
    .template-preview {
        height: 120px;
        overflow: hidden;
        background: var(--body-bg);
        position: relative;
    }
    .template-preview iframe {
        width: 600px;
        height: 600px;
        transform: scale(0.26);
        transform-origin: top left;
        pointer-events: none;
        border: none;
    }
    .template-name {
        padding: 0.5rem;
        text-align: center;
        font-size: 0.8rem;
        font-weight: 600;
        border-top: 1px solid #e3e6f0;
    }
    .editor-tabs .nav-link {
        font-size: 0.9rem;
    }
    .editor-tabs .nav-link.active {
        font-weight: 600;
    }
    #htmlEditor {
        font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
        font-size: 0.82rem;
        line-height: 1.5;
        tab-size: 2;
        resize: vertical;
    }
</style>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Create Campaign</h5>
        <a href="<?= base_url('campaigns/') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
    <div class="card-body">
        <form id="campaignForm" action="<?= base_url('campaigns/store') ?>" method="POST">
            <?= csrf_field() ?>

            <!-- Step 1: Basic Info -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Select Audiences</label>
                    <small class="form-text d-block mb-1">Hold Ctrl/Cmd to select multiple</small>
                    <select class="form-select" name="audiences[]" id="audiences" multiple required style="min-height: 100px;">
                        <?php foreach ($audiences as $audience): ?>
                            <option value="<?= esc($audience['id']) ?>"><?= esc($audience['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Campaign Name</label>
                    <input class="form-control" type="text" name="campaign_name" id="campaign_name" required>
                    <div class="form-text">Ge kampanjen ett namn</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subject Line</label>
                    <input class="form-control" type="text" name="subject" id="subject" required placeholder="Your email subject...">
                </div>
            </div>

            <!-- Editor Tabs: AI / HTML / Templates -->
            <ul class="nav nav-tabs editor-tabs mb-0" id="editorTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-ai" data-bs-toggle="tab" data-bs-target="#panel-ai" type="button" role="tab">
                        <i class="fas fa-magic me-1"></i> Skapa med AI
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-html" data-bs-toggle="tab" data-bs-target="#panel-html" type="button" role="tab">
                        <i class="fas fa-code me-1"></i> Klistra in HTML
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-templates" data-bs-toggle="tab" data-bs-target="#panel-templates" type="button" role="tab">
                        <i class="fas fa-palette me-1"></i> Sparade mallar <?php if (!empty($templates)): ?><span class="badge bg-primary ms-1"><?= count($templates) ?></span><?php endif; ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom p-4 mb-4" style="background: #fafbfe;">

                <!-- Tab: AI Generator -->
                <div class="tab-pane fade show active" id="panel-ai" role="tabpanel">
                    <h6 class="mb-2"><i class="fas fa-magic me-2"></i>AI Email Generator</h6>
                    <p class="text-muted mb-2" style="font-size: 0.8125rem;">
                        Välj en snabbmall eller skriv egen beskrivning. AI:n genererar proffsig HTML med unik hero-bild.
                    </p>

                    <!-- Quick prompts -->
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary quick-prompt" data-prompt="Skapa ett välkomstmail för nya kunder. Varm hälsning, introduktion till våra tjänster, och en CTA-knapp att utforska sortimentet.">
                            <i class="fas fa-hand-wave me-1"></i> Välkomstmail
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-prompt" data-prompt="Skapa ett nyhetsbrev med senaste nyheter och uppdateringar. Inkludera en hero-bild, 2-3 artikelsektioner med rubriker och kort text, och en CTA-knapp.">
                            <i class="fas fa-newspaper me-1"></i> Nyhetsbrev
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-prompt" data-prompt="Skapa ett kampanjmail med ett tidsbegränsat erbjudande. Inkludera en iögonfallande hero-bild, erbjudandedetaljer i en framhävd ruta, rabattkod, och en tydlig CTA-knapp.">
                            <i class="fas fa-tag me-1"></i> Erbjudande/Kampanj
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-prompt" data-prompt="Skapa ett produktlanseringsmail. Hero-bild av produkten, produktbeskrivning, 3 key features med ikoner, pris, och en köp-knapp.">
                            <i class="fas fa-rocket me-1"></i> Produktlansering
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-prompt" data-prompt="Skapa ett re-engagement mail till inaktiva kunder. Vi saknar dig-tema, erbjud incitament att komma tillbaka, visa vad de missat, och en stark CTA.">
                            <i class="fas fa-heart me-1"></i> Win-back
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-prompt" data-prompt="Skapa ett event-inbjudningsmail. Eventnamn, datum, tid, plats, kort beskrivning, och en anmälningsknapp.">
                            <i class="fas fa-calendar me-1"></i> Event/Inbjudan
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-2">
                            <textarea id="aiPrompt" class="form-control" rows="3"
                                placeholder="Beskriv mailet du vill skapa... eller klicka på en snabbmall ovan och anpassa."></textarea>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label" style="font-size: 0.8125rem;">Visuell inspiration (valfritt)</label>
                            <input type="file" id="aiImage" class="form-control form-control-sm" accept="image/png,image/jpeg,image/gif,image/webp">
                            <small class="form-text">Bifoga en skärmbild som referens</small>
                        </div>
                    </div>
                    <button type="button" id="aiGenerateBtn" class="btn btn-primary mt-2">
                        <i class="fas fa-magic me-1"></i> Generera med AI
                    </button>
                    <div id="aiLoading" class="mt-2" style="display:none;">
                        <div class="d-flex align-items-center gap-2" style="color: var(--primary);">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                            <span style="font-size: 0.875rem;">Genererar mail + hero-bild... detta kan ta 20-40 sekunder</span>
                        </div>
                    </div>
                    <div id="aiError" class="alert alert-danger mt-2 mb-0 py-2" style="display:none; font-size: 0.85rem;"></div>
                </div>

                <!-- Tab: Paste HTML -->
                <div class="tab-pane fade" id="panel-html" role="tabpanel">
                    <h6 class="mb-2"><i class="fas fa-code me-2"></i>Klistra in HTML</h6>
                    <p class="text-muted mb-2" style="font-size: 0.85rem;">
                        Klistra in HTML-koden från Klaviyo, Mailchimp eller valfri källa. Koden bevaras exakt som den är.
                    </p>
                    <button type="button" id="pasteHtmlBtn" class="btn btn-sm btn-outline-primary mb-2">
                        <i class="fas fa-paste me-1"></i> Klistra in HTML i editorn nedan
                    </button>
                </div>

                <!-- Tab: Templates -->
                <div class="tab-pane fade" id="panel-templates" role="tabpanel">
                    <h6 class="mb-2"><i class="fas fa-palette me-2"></i>Sparade mallar</h6>
                    <?php if (!empty($templates)): ?>
                    <div class="template-picker" id="templatePicker">
                        <?php foreach ($templates as $template): ?>
                            <div class="template-card" data-html="<?= htmlspecialchars($template['content'], ENT_QUOTES) ?>">
                                <div class="template-preview">
                                    <iframe srcdoc="<?= htmlspecialchars($template['content'], ENT_QUOTES) ?>" sandbox></iframe>
                                </div>
                                <div class="template-name d-flex justify-content-between align-items-center">
                                    <span class="text-truncate"><?= esc($template['title'] ?? 'Untitled') ?></span>
                                    <?php if (!empty($template['id'])): ?>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 delete-template-btn" data-template-id="<?= esc($template['id']) ?>" title="Ta bort"><i class="fas fa-trash-alt" style="font-size: 0.7rem;"></i></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted" style="font-size: 0.85rem;">Inga sparade mallar ännu. Skapa en kampanj och klicka "Spara som mall" i editorn.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- HTML Editor + Preview -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
                    <h6 class="mb-0"><i class="fas fa-file-code me-2"></i>Email HTML</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" id="uploadImageBtn" class="btn btn-sm btn-outline-secondary" title="Ladda upp logo eller bild">
                            <i class="fas fa-image me-1"></i> Bild
                        </button>
                        <input type="file" id="imageUploadInput" accept="image/png,image/jpeg,image/gif,image/webp" style="display:none;">
                        <button type="button" id="saveTemplateBtn" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-save me-1"></i> Spara som mall
                        </button>
                        <button type="button" id="optimizeAiBtn" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-wand-magic-sparkles me-1"></i> Optimera med AI
                        </button>
                        <button type="button" id="sendPreviewBtn" class="btn btn-sm btn-accent" title="Skicka preview till din mail">
                            <i class="fas fa-envelope me-1"></i> Skicka Preview
                        </button>
                    </div>
                </div>

                <!-- Preview (shown by default when content exists) -->
                <div class="card-body p-0" id="previewContainer">
                    <div id="previewEmpty" class="text-center py-5" style="color: var(--text-muted);">
                        <i class="fas fa-envelope-open d-block mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p class="mb-0" style="font-size: 0.8125rem;">Generera eller klistra in ett mail för att se förhandsgranskning här.</p>
                    </div>
                    <iframe id="previewFrame" style="width: 100%; height: 600px; border: none; display: none;"></iframe>
                </div>

                <!-- Toggle to show HTML code -->
                <div class="card-footer py-2 d-flex justify-content-between align-items-center" style="border-top: 1px solid var(--card-border, #e5e7eb);">
                    <button type="button" id="toggleHtmlBtn" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-code me-1"></i> Visa HTML-kod
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" id="optimizeAiBtn" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-wand-magic-sparkles me-1"></i> Optimera med AI
                        </button>
                    </div>
                </div>

                <!-- HTML Editor (hidden by default) -->
                <div id="htmlEditorContainer" class="card-body p-0" style="display: none;">
                    <textarea id="htmlEditor" class="form-control border-0 rounded-0" rows="18" placeholder="Din email-HTML visas här..."
                              style="min-height: 300px; font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace; font-size: 0.8rem;"></textarea>
                </div>
            </div>

            <!-- AI Optimize Panel (hidden by default) -->
            <div id="optimizePanel" class="card mb-4" style="display: none; border-color: var(--primary, #4F46E5);">
                <div class="card-body">
                    <h6 class="mb-2"><i class="fas fa-wand-magic-sparkles me-2"></i>Optimera med AI</h6>
                    <p class="text-muted mb-2" style="font-size: 0.8125rem;">
                        Beskriv vad du vill ändra — AI:n uppdaterar din design.
                    </p>
                    <textarea id="optimizePrompt" class="form-control mb-2" rows="2"
                        placeholder="T.ex: Byt header-bilden, ändra knappfärgen till grön, lägg till en sektion med 3 produkter..."></textarea>
                    <div class="d-flex gap-2">
                        <button type="button" id="optimizeGoBtn" class="btn btn-sm btn-primary">
                            <i class="fas fa-magic me-1"></i> Kör optimering
                        </button>
                        <button type="button" id="optimizeCancelBtn" class="btn btn-sm btn-outline-secondary">
                            Avbryt
                        </button>
                    </div>
                    <div id="optimizeLoading" class="mt-2" style="display:none;">
                        <div class="d-flex align-items-center gap-2" style="color: var(--primary);">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                            <span style="font-size: 0.875rem;">Optimerar din HTML... detta kan ta 15-30 sekunder</span>
                        </div>
                    </div>
                    <div id="optimizeError" class="alert alert-danger mt-2 mb-0 py-2" style="display:none; font-size: 0.85rem;"></div>
                </div>
            </div>

            <!-- Legacy preview container removed — preview is now inline above -->
                </div>
            </div>

            <!-- Save as Template Modal -->
            <div class="modal fade" id="saveTemplateModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-save me-2"></i>Spara som mall</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Mallnamn</label>
                            <input type="text" id="templateNameInput" class="form-control" placeholder="T.ex: Välkomstmail, Nyhetsbrev, Black Friday...">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                            <button type="button" id="confirmSaveTemplate" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Spara
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden fields synced on submit -->
            <input type="hidden" name="contentHTML" id="contentHTML">
            <input type="hidden" name="contentPlainText" id="contentPlainText">
            <input type="hidden" name="grapesJSData" id="grapesJSData" value="">
            <input type="hidden" name="editor_mode" id="editorMode" value="raw">

            <div class="d-flex justify-content-end mt-4 gap-2">
                <a href="<?= base_url('campaigns/') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Campaign</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    var htmlEditor = document.getElementById('htmlEditor');

    // --- Quick prompts ---
    document.querySelectorAll('.quick-prompt').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var prompt = this.dataset.prompt;
            document.getElementById('aiPrompt').value = prompt;
            document.getElementById('aiPrompt').focus();
            // Scroll to prompt area
            document.getElementById('aiPrompt').scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof showToast === 'function') {
                showToast('Mall inladdad! Anpassa texten och klicka Generera.', 'info');
            }
        });
    });

    // --- Send Preview Email ---
    document.getElementById('sendPreviewBtn').addEventListener('click', function() {
        var html = htmlEditor.value.trim();
        if (!html) {
            alert('Ingen HTML att skicka. Generera eller klistra in HTML först.');
            return;
        }
        var email = prompt('Ange e-postadress att skicka preview till:');
        if (!email) return;

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Skickar...';

        var subject = document.getElementById('subject').value || 'Test Email';
        var formData = new FormData();
        formData.append('preview_email', email);
        formData.append('subject', subject);
        formData.append('html', html);
        formData.append('csrf_test_name', document.querySelector('meta[name="csrf-token"]').content);

        fetch(BASE_URL + 'campaigns/send-preview', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.csrf_token) syncCsrfTokens(data.csrf_token);
            if (data.success) {
                if (typeof showToast === 'function') showToast(data.message, 'success');
            } else {
                alert(data.message || 'Kunde inte skicka preview');
            }
        })
        .catch(function(err) { alert('Fel: ' + err.message); })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-envelope me-1"></i> Preview';
        });
    });

    // --- Upload Image ---
    document.getElementById('uploadImageBtn').addEventListener('click', function() {
        document.getElementById('imageUploadInput').click();
    });

    document.getElementById('imageUploadInput').addEventListener('change', function() {
        if (!this.files.length) return;
        var formData = new FormData();
        formData.append('image', this.files[0]);
        formData.append('csrf_test_name', document.querySelector('meta[name="csrf-token"]').content);

        var btn = document.getElementById('uploadImageBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Laddar...';

        fetch(BASE_URL + 'campaigns/upload-image', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.csrf_token) syncCsrfTokens(data.csrf_token);
            if (data.success && data.url) {
                // Copy URL to clipboard and show toast
                navigator.clipboard.writeText(data.url).then(function() {
                    if (typeof showToast === 'function') {
                        showToast('Bild uppladdad! URL kopierad. Klistra in i HTML eller be AI använda den.', 'success');
                    }
                });
                // Also insert into HTML if editor has content with placehold.co
                var currentHtml = htmlEditor.value;
                if (currentHtml.indexOf('placehold.co') !== -1) {
                    htmlEditor.value = currentHtml.replace(/https:\/\/placehold\.co\/[^"'\s]+/, data.url);
                    updatePreview();
                    if (typeof showToast === 'function') {
                        showToast('Placeholder-bild ersatt med din uppladdade bild!', 'success');
                    }
                } else {
                    updatePreview();
                }
            } else {
                alert(data.message || 'Kunde inte ladda upp bilden');
            }
        })
        .catch(function(err) { alert('Fel: ' + err.message); })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-image me-1"></i> Bild';
            document.getElementById('imageUploadInput').value = '';
        });
    });

    // --- Paste HTML into editor ---
    document.getElementById('pasteHtmlBtn').addEventListener('click', function() {
        htmlEditor.focus();
        htmlEditor.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (typeof showToast === 'function') {
            showToast('Klistra in din HTML-kod i editorn nedan (Ctrl+V / Cmd+V)', 'info');
        }
    });

    // --- Template picker ---
    document.querySelectorAll('.template-card').forEach(function(card) {
        card.addEventListener('click', function() {
            document.querySelectorAll('.template-card').forEach(function(c) {
                c.classList.remove('selected');
            });
            this.classList.add('selected');

            var html = this.dataset.html;
            if (html) {
                htmlEditor.value = html;
                updatePreview();
                document.getElementById('previewContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
                if (typeof showToast === 'function') {
                    showToast('Template inladdad!', 'success');
                }
            }
        });
    });

    // --- Auto-update preview when HTML changes ---
    function updatePreview() {
        var html = htmlEditor.value.trim();
        var previewFrame = document.getElementById('previewFrame');
        var previewEmpty = document.getElementById('previewEmpty');
        if (html) {
            previewFrame.srcdoc = html;
            previewFrame.style.display = 'block';
            previewEmpty.style.display = 'none';
        } else {
            previewFrame.style.display = 'none';
            previewEmpty.style.display = 'block';
        }
    }

    // Update preview on input (debounced)
    var previewTimer = null;
    htmlEditor.addEventListener('input', function() {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(updatePreview, 500);
    });

    // --- Toggle HTML editor visibility ---
    document.getElementById('toggleHtmlBtn').addEventListener('click', function() {
        var container = document.getElementById('htmlEditorContainer');
        var isVisible = container.style.display !== 'none';
        container.style.display = isVisible ? 'none' : 'block';
        this.innerHTML = isVisible
            ? '<i class="fas fa-code me-1"></i> Visa HTML-kod'
            : '<i class="fas fa-eye me-1"></i> Dölj HTML-kod';
        if (!isVisible) {
            htmlEditor.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    // --- AI Generate ---
    document.getElementById('aiGenerateBtn').addEventListener('click', function() {
        var prompt = document.getElementById('aiPrompt').value.trim();
        if (!prompt) {
            var errorDiv = document.getElementById('aiError');
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'Skriv en beskrivning av mailet du vill skapa.';
            return;
        }

        var btn = this;
        var loading = document.getElementById('aiLoading');
        var errorDiv = document.getElementById('aiError');

        btn.disabled = true;
        loading.style.display = 'block';
        errorDiv.style.display = 'none';

        var formData = new FormData();
        formData.append('prompt', prompt);

        var imageInput = document.getElementById('aiImage');
        if (imageInput.files.length > 0) {
            formData.append('image', imageInput.files[0]);
        }

        fetch(BASE_URL + 'campaigns/generate', {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            if (!response.ok) {
                return response.text().then(function(text) {
                    console.error('Server response (' + response.status + '):', text.substring(0, 500));
                    throw new Error('Server error: ' + response.status);
                });
            }
            var contentType = response.headers.get('content-type') || '';
            if (contentType.indexOf('application/json') === -1) {
                return response.text().then(function(text) {
                    console.error('Non-JSON response:', text.substring(0, 500));
                    throw new Error('Unexpected response format');
                });
            }
            return response.json();
        })
        .then(function(data) {
            if (data.csrf_token) syncCsrfTokens(data.csrf_token);

            if (data.success) {
                htmlEditor.value = data.html;
                updatePreview(); // Auto-show preview

                var subjectField = document.getElementById('subject');
                if (data.subject && !subjectField.value) {
                    subjectField.value = data.subject;
                }

                document.getElementById('previewContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });

                if (typeof showToast === 'function') {
                    showToast('Email genererad! Förhandsgranskning visas nedan.', 'success');
                }
            } else {
                errorDiv.style.display = 'block';
                errorDiv.textContent = data.error || 'Generering misslyckades. Försök igen.';
            }
        })
        .catch(function(err) {
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'Fel: ' + err.message + '. Försök ladda om sidan och testa igen.';
            console.error('AI generation error:', err);
        })
        .finally(function() {
            btn.disabled = false;
            loading.style.display = 'none';
        });
    });

    // --- AI Optimize ---
    document.getElementById('optimizeAiBtn').addEventListener('click', function() {
        var html = htmlEditor.value.trim();
        if (!html) {
            alert('Ingen HTML att optimera. Skapa eller klistra in HTML först.');
            return;
        }
        document.getElementById('optimizePanel').style.display = 'block';
        document.getElementById('optimizePanel').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    document.getElementById('optimizeCancelBtn').addEventListener('click', function() {
        document.getElementById('optimizePanel').style.display = 'none';
    });

    document.getElementById('optimizeGoBtn').addEventListener('click', function() {
        var currentHtml = htmlEditor.value.trim();
        var optimizePrompt = document.getElementById('optimizePrompt').value.trim();

        if (!currentHtml) {
            alert('Ingen HTML att optimera.');
            return;
        }

        var fullPrompt = 'Optimize and improve this existing email HTML template. ';
        if (optimizePrompt) {
            fullPrompt += 'User instructions: ' + optimizePrompt + '. ';
        }
        fullPrompt += 'Keep the same content and structure but improve the design, make it more modern and professional. Here is the current HTML:\n\n' + currentHtml;

        var btn = this;
        var loading = document.getElementById('optimizeLoading');
        var errorDiv = document.getElementById('optimizeError');

        btn.disabled = true;
        loading.style.display = 'block';
        errorDiv.style.display = 'none';

        var formData = new FormData();
        formData.append('prompt', fullPrompt);

        fetch(BASE_URL + 'campaigns/generate', {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            if (!response.ok) throw new Error('Server error: ' + response.status);
            return response.json();
        })
        .then(function(data) {
            if (data.csrf_token) syncCsrfTokens(data.csrf_token);

            if (data.success) {
                htmlEditor.value = data.html;
                updatePreview();
                document.getElementById('optimizePanel').style.display = 'none';
                document.getElementById('previewContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });

                if (typeof showToast === 'function') {
                    showToast('HTML optimerad! Kolla resultatet nedan.', 'success');
                }
            } else {
                errorDiv.style.display = 'block';
                errorDiv.textContent = data.error || 'Optimering misslyckades. Försök igen.';
            }
        })
        .catch(function(err) {
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'Fel: ' + err.message;
        })
        .finally(function() {
            btn.disabled = false;
            loading.style.display = 'none';
        });
    });

    // --- Save as Template ---
    document.getElementById('saveTemplateBtn').addEventListener('click', function() {
        var html = htmlEditor.value.trim();
        if (!html) {
            alert('Ingen HTML att spara som mall. Skapa eller klistra in HTML först.');
            return;
        }
        document.getElementById('templateNameInput').value = '';
        var modal = new bootstrap.Modal(document.getElementById('saveTemplateModal'));
        modal.show();
    });

    document.getElementById('confirmSaveTemplate').addEventListener('click', function() {
        var name = document.getElementById('templateNameInput').value.trim();
        if (!name) {
            alert('Ange ett namn för mallen.');
            return;
        }

        var html = htmlEditor.value.trim();
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = BASE_URL + 'campaigns/save-template';

        var csrfInput = document.querySelector('input[name="csrf_test_name"]');
        if (csrfInput) {
            var csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = 'csrf_test_name';
            csrf.value = csrfInput.value;
            form.appendChild(csrf);
        }

        var nameField = document.createElement('input');
        nameField.type = 'hidden';
        nameField.name = 'template_name';
        nameField.value = name;
        form.appendChild(nameField);

        var htmlField = document.createElement('input');
        htmlField.type = 'hidden';
        htmlField.name = 'template_html';
        htmlField.value = html;
        form.appendChild(htmlField);

        document.body.appendChild(form);
        form.submit();
    });

    // --- Delete template (via JS to avoid nested forms) ---
    document.querySelectorAll('.delete-template-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!confirm('Ta bort denna mall?')) return;
            var id = this.dataset.templateId;
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = BASE_URL + 'campaigns/delete-template/' + id;
            var csrfInput = document.querySelector('input[name="csrf_test_name"]');
            if (csrfInput) {
                var csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = 'csrf_test_name';
                csrf.value = csrfInput.value;
                form.appendChild(csrf);
            }
            document.body.appendChild(form);
            form.submit();
        });
    });

    // --- Keep CSRF token in sync across all forms ---
    function syncCsrfTokens(newToken) {
        if (!newToken) return;
        // Update meta tag
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', newToken);
        // Update ALL hidden csrf fields in ALL forms
        document.querySelectorAll('input[name="csrf_test_name"]').forEach(function(input) {
            input.value = newToken;
        });
    }

    // --- Form submit ---
    document.getElementById('campaignForm').addEventListener('submit', function(e) {
        // Sync CSRF token from meta to form before submit
        var metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            document.querySelectorAll('input[name="csrf_test_name"]').forEach(function(input) {
                input.value = metaToken.getAttribute('content');
            });
        }

        var html = htmlEditor.value.trim();

        if (!html) {
            e.preventDefault();
            alert('Du måste ha HTML-innehåll innan du kan spara kampanjen.');
            return;
        }

        document.getElementById('contentHTML').value = html;
        document.getElementById('grapesJSData').value = '';

        // Auto-generate plain text
        var temp = document.createElement('div');
        temp.innerHTML = html;
        document.getElementById('contentPlainText').value = temp.textContent || temp.innerText || '';
    });

});
</script>
