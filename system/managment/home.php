<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الصفحة الرئيسية - Baserow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #e0e7ff 0%, #f3e8ff 100%);
            color: #1e293b;
            overflow-x: hidden;
        }
        .container {
            max-width: 85rem;
            margin: 0 auto;
            padding: 2rem;
        }
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 1.25rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        .btn {
            padding: 0.75rem 2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.4s ease, height 0.4s ease;
        }
        .btn:hover::after {
            width: 200%;
            height: 200%;
        }
        .btn-primary {
            background: linear-gradient(45deg, #3b82f6, #1e40af);
            color: #ffffff;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #2563eb, #1e3a8a);
            transform: translateY(-3px);
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #1e293b;
        }
        .btn-secondary:hover {
            background: #d1d5db;
            transform: translateY(-3px);
        }
        .btn-danger {
            background: linear-gradient(45deg, #ef4444, #b91c1c);
            color: #ffffff;
        }
        .btn-danger:hover {
            background: linear-gradient(45deg, #dc2626, #991b1b);
            transform: translateY(-3px);
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease-in-out;
        }
        .modal:not(.hidden) {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 1.25rem;
            padding: 2.5rem;
            max-width: 90%;
            width: 36rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }
        .modal:not(.hidden) .modal-content {
            transform: scale(1);
        }
        .input, .select, .textarea {
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            padding: 0.85rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        .input:focus, .select:focus, .textarea:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            outline: none;
            background: #ffffff;
        }
        .drop-zone {
            border: 2px dashed #a5b4fc;
            padding: 2rem;
            text-align: center;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        .drop-zone.dragover {
            border-color: #3b82f6;
            background: #eff6ff;
            transform: scale(1.02);
        }
        .image-preview {
            max-width: 140px;
            max-height: 140px;
            object-fit: cover;
            border-radius: 0.75rem;
            border: 1px solid #d1d5db;
            transition: transform 0.3s ease;
        }
        .image-preview:hover {
            transform: scale(1.05);
        }
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 1rem 2rem;
            border-radius: 0.75rem;
            color: #ffffff;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.4s ease, transform 0.4s ease;
            transform: translateY(20px);
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .toast.success {
            background: linear-gradient(45deg, #22c55e, #16a34a);
        }
        .toast.error {
            background: linear-gradient(45deg, #ef4444, #b91c1c);
        }
        .tab {
            padding: 0.85rem 2rem;
            font-weight: 600;
            color: #64748b;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            position: relative;
        }
        .tab::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: #3b82f6;
            transition: width 0.3s ease;
        }
        .tab:hover::after, .tab.active::after {
            width: 100%;
        }
        .tab.active {
            color: #3b82f6;
        }
        .tab:hover {
            color: #1e40af;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 0.75rem;
            overflow: hidden;
            background: #ffffff;
        }
        th, td {
            padding: 1.25rem;
            text-align: right;
        }
        th {
            background: linear-gradient(45deg, #f8fafc, #e0e7ff);
            font-weight: 700;
            color: #1e293b;
        }
        tr {
            transition: background 0.3s ease;
        }
        tr:hover {
            background: #eff6ff;
            transform: scale(1.005);
        }
        .loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @media (max-width: 640px) {
            .container {
                padding: 1rem;
            }
            .modal-content {
                width: 95%;
                padding: 1.5rem;
            }
            .btn {
                padding: 0.6rem 1.5rem;
            }
            .input, .select, .textarea {
                font-size: 0.85rem;
            }
            th, td {
                padding: 0.85rem;
                font-size: 0.8rem;
            }
            .tab {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-3xl md:text-4xl font-bold text-center mb-10 text-gray-900 animate-fadeIn">إدارة الصفحة الرئيسية</h1>

        <!-- Tabs for Locations -->
        <div class="flex flex-wrap gap-4 mb-8 border-b border-indigo-100">
            <?php foreach ($locations as $loc): ?>
                <a href="?location=<?= urlencode($loc) ?>&page=1&page_size=<?= $page_size ?>" class="tab <?= $selected_location === $loc ? 'active' : '' ?>">
                    <?= htmlspecialchars($loc) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="p-4 mb-8 rounded-lg bg-opacity-95 <?= $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> animate-slideIn">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Add catalog form -->
        <div class="card p-8 mb-10">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">إضافة كتالوج جديد</h2>
            <form id="addCatalogForm" method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <input name="order" type="text" placeholder="ترتيب" class="input">
                    <input name="sub_order" type="text" placeholder="ترتيب فرعي" class="input">
                    <input name="name_ar" type="text" placeholder="الاسم (بالعربية)" class="input" required>
                    <input name="name_en" type="text" placeholder="الاسم (بالإنجليزية)" class="input">
                    <input name="sub_name_ar" type="text" placeholder="الاسم الفرعي (بالعربية)" class="input">
                    <input name="sub_name_en" type="text" placeholder="الاسم الفرعي (بالإنجليزية)" class="input">
                    <select name="status" class="select">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="location" class="select">
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= htmlspecialchars($loc) ?>" <?= $selected_location === $loc ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="lg:col-span-2">
                        <div class="drop-zone" id="addDropZone">اسحب الصورة هنا أو انقر لاختيار ملف</div>
                        <input id="addCatalogImage" name="catalog_image" type="file" accept="image/*" class="hidden">
                        <div id="addImagePreview" class="hidden mt-4">
                            <img src="" alt="معاينة الصورة" class="image-preview">
                            <button type="button" onclick="clearAddImage()" class="mt-3 btn btn-danger">إزالة الصورة</button>
                        </div>
                    </div>
                    <input name="file_id" type="text" placeholder="معرف الملف" class="input">
                    <textarea name="description_ar" placeholder="نص الوصف (بالعربية)" class="textarea col-span-2"></textarea>
                    <textarea name="description_en" placeholder="نص الوصف (بالإنجليزية)" class="textarea col-span-2"></textarea>
                </div>
                <button type="submit" name="add_catalog" class="mt-8 btn btn-primary">إضافة الكتالوج</button>
            </form>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col sm:flex-row justify-between mb-8 items-center gap-6">
            <a href="?location=<?= urlencode($selected_location) ?>&page=<?= $page - 1 ?>&page_size=<?= $page_size ?>" class="btn btn-primary <?= $previous_page_url ? '' : 'opacity-50 pointer-events-none' ?>">الصفحة السابقة</a>
            <div class="flex items-center gap-6">
                <form method="GET" class="inline-flex items-center">
                    <input type="hidden" name="location" value="<?= htmlspecialchars($selected_location) ?>">
                    <label for="page_size" class="text-gray-700 font-medium ml-2">عدد الكتالوجات في الصفحة:</label>
                    <select name="page_size" onchange="this.form.submit()" class="select">
                        <option value="10" <?= $page_size == 10 ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= $page_size == 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= $page_size == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $page_size == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </form>
                <span class="text-gray-700 font-medium">الصفحة <?= $page ?> من <?= $total_pages ?> (إجمالي الكتالوجات: <?= $total_count ?>)</span>
            </div>
            <a href="?location=<?= urlencode($selected_location) ?>&page=<?= $page + 1 ?>&page_size=<?= $page_size ?>" class="btn btn-primary <?= $next_page_url ? '' : 'opacity-50 pointer-events-none' ?>">الصفحة التالية</a>
        </div>

        <!-- Delete Modal -->
        <div id="deleteModal" class="modal hidden">
            <div class="modal-content">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">تأكيد الحذف</h3>
                <p class="mb-6 text-gray-600">هل أنت متأكد من حذف هذا الكتالوج؟</p>
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="catalog_id" id="deleteCatalogId">
                    <div class="flex justify-end gap-4">
                        <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">إلغاء</button>
                        <button type="submit" name="delete_catalog" class="btn btn-danger">تأكيد</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Modal -->
        <div id="updateModal" class="modal hidden">
            <div class="modal-content max-w-3xl">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">تحديث الكتالوج</h3>
                <form id="updateForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="catalog_id" id="updateCatalogId">
                    <input type="hidden" name="current_image" id="currentImage">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <input name="order" id="updateOrder" type="text" placeholder="ترتيب" class="input">
                        <input name="sub_order" id="updateSubOrder" type="text" placeholder="ترتيب فرعي" class="input">
                        <input name="name_ar" id="updateNameAr" type="text" placeholder="الاسم (بالعربية)" class="input" required>
                        <input name="name_en" id="updateNameEn" type="text" placeholder="الاسم (بالإنجليزية)" class="input">
                        <input name="sub_name_ar" id="updateSubNameAr" type="text" placeholder="الاسم الفرعي (بالعربية)" class="input">
                        <input name="sub_name_en" id="updateSubNameEn" type="text" placeholder="الاسم الفرعي (بالإنجليزية)" class="input">
                        <select name="status" id="updateStatus" class="select">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="location" id="updateLocation" class="select">
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="lg:col-span-2">
                            <div class="drop-zone" id="updateDropZone">اسحب الصورة هنا أو انقر لاختيار ملف</div>
                            <input id="updateCatalogImage" name="catalog_image" type="file" accept="image/*" class="hidden">
                            <div id="updateImagePreview" class="hidden mt-4">
                                <img src="" alt="معاينة الصورة" class="image-preview">
                                <button type="button" onclick="clearUpdateImage()" class="mt-3 btn btn-danger">إزالة الصورة</button>
                            </div>
                        </div>
                        <input name="file_id" id="updateFileId" type="text" placeholder="معرف الملف" class="input">
                        <textarea name="description_ar" id="updateDescriptionAr" placeholder="نص الوصف (بالعربية)" class="textarea col-span-2"></textarea>
                        <textarea name="description_en" id="updateDescriptionEn" placeholder="نص الوصف (بالإنجليزية)" class="textarea col-span-2"></textarea>
                    </div>
                    <div class="flex justify-end gap-4 mt-8">
                        <button type="button" onclick="closeUpdateModal()" class="btn btn-secondary">إلغاء</button>
                        <button type="submit" name="update_catalog" class="btn btn-primary">تحديث</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Catalogs table -->
        <div class="card p-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6"><?= htmlspecialchars($selected_location) ?></h2>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>ترتيب</th>
                            <th>الاسم (عربي)</th>
                            <th>الاسم (إنجليزي)</th>
                            <th>الموقع</th>
                            <th>الحالة</th>
                            <th>الصورة</th>
                            <th>الرابط</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($catalogs)): ?>
                            <tr><td colspan="8" class="text-center text-gray-600 py-4">لا توجد بيانات متاحة لموقع "<?= htmlspecialchars($selected_location) ?>"</td></tr>
                        <?php else: ?>
                            <?php foreach ($catalogs as $catalog): ?>
                                <tr>
                                    <td><?= htmlspecialchars($catalog['field_6759'] ?? 'غير متوفر') ?></td>
                                    <td><?= htmlspecialchars($catalog['field_6754'] ?? 'غير متوفر') ?></td>
                                    <td><?= htmlspecialchars($catalog['field_6762'] ?? 'غير متوفر') ?></td>
                                    <td><?= htmlspecialchars($catalog['field_6756'] ?? 'غير متوفر') ?></td>
                                    <td><?= htmlspecialchars($catalog['field_7072'] ?? 'غير متوفر') ?></td>
                                    <td>
                                        <?php if (!empty($catalog['field_6755'])): ?>
                                            <img src="<?= htmlspecialchars($catalog['field_6755']) ?>" alt="<?= htmlspecialchars($catalog['field_6754'] ?? 'كتالوج') ?>" class="w-14 h-14 object-cover rounded-lg">
                                        <?php else: ?>
                                            غير متوفر
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($catalog['field_6757'])): ?>
                                            <a href="<?= htmlspecialchars($catalog['field_6757']) ?>" target="_blank" class="text-blue-600 hover:underline">عرض الرابط</a>
                                        <?php else: ?>
                                            غير متوفر
                                        <?php endif; ?>
                                    </td>
                                    <td class="flex gap-3">
                                        <button type="button" onclick="openUpdateModal(<?= $catalog['id'] ?>, '<?= htmlspecialchars($catalog['field_6759'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6760'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6754'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6762'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6761'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_7075'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_7072'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6755'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6757'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6758'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_7076'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_7077'] ?? '') ?>', '<?= htmlspecialchars($catalog['field_6756'] ?? '') ?>')" class="btn btn-primary">تحرير</button>
                                        <button type="button" onclick="openDeleteModal(<?= $catalog['id'] ?>)" class="btn btn-danger">حذف</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Toast Notification -->
        <div id="toast" class="toast"></div>
    </div>

    <script>
        // Show toast notification
        function showToast(message, type) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => {
                toast.className = 'toast';
            }, 4000);
        }

        // Open delete modal
        function openDeleteModal(catalogId) {
            document.getElementById('deleteCatalogId').value = catalogId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        // Close delete modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteCatalogId').value = '';
        }

        // Open update modal
        function openUpdateModal(catalogId, order, subOrder, nameAr, nameEn, subNameAr, subNameEn, status, catalogImage, link, fileId, descriptionAr, descriptionEn, location) {
            document.getElementById('updateCatalogId').value = catalogId;
            document.getElementById('updateOrder').value = order;
            document.getElementById('updateSubOrder').value = subOrder;
            document.getElementById('updateNameAr').value = nameAr;
            document.getElementById('updateNameEn').value = nameEn;
            document.getElementById('updateSubNameAr').value = subNameAr;
            document.getElementById('updateSubNameEn').value = subNameEn;
            document.getElementById('updateStatus').value = status;
            document.getElementById('updateLocation').value = location;
            document.getElementById('currentImage').value = catalogImage;
            document.getElementById('updateFileId').value = fileId;
            document.getElementById('updateDescriptionAr').value = descriptionAr;
            document.getElementById('updateDescriptionEn').value = descriptionEn;
            const preview = document.getElementById('updateImagePreview');
            if (catalogImage) {
                preview.querySelector('img').src = catalogImage;
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
            document.getElementById('updateModal').classList.remove('hidden');
        }

        // Close update modal
        function closeUpdateModal() {
            document.getElementById('updateModal').classList.add('hidden');
            document.getElementById('updateForm').reset();
            document.getElementById('updateImagePreview').classList.add('hidden');
            document.getElementById('updateCatalogImage').value = '';
        }

        // Clear add image
        function clearAddImage() {
            document.getElementById('addCatalogImage').value = '';
            document.getElementById('addImagePreview').classList.add('hidden');
        }

        // Clear update image
        function clearUpdateImage() {
            document.getElementById('updateCatalogImage').value = '';
            document.getElementById('updateImagePreview').classList.add('hidden');
        }

        // Image preview and drag-and-drop for add form
        const addDropZone = document.getElementById('addDropZone');
        const addFileInput = document.getElementById('addCatalogImage');
        addDropZone.addEventListener('click', () => addFileInput.click());
        addDropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            addDropZone.classList.add('dragover');
        });
        addDropZone.addEventListener('dragleave', () => {
            addDropZone.classList.remove('dragover');
        });
        addDropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            addDropZone.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                addFileInput.files = e.dataTransfer.files;
                const reader = new FileReader();
                reader.onload = (e) => {
                    const preview = document.getElementById('addImagePreview');
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                showToast('يرجى اختيار صورة صالحة', 'error');
            }
        });
        addFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            const preview = document.getElementById('addImagePreview');
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('hidden');
                showToast('يرجى اختيار صورة صالحة', 'error');
            }
        });

        // Image preview and drag-and-drop for update form
        const updateDropZone = document.getElementById('updateDropZone');
        const updateFileInput = document.getElementById('updateCatalogImage');
        updateDropZone.addEventListener('click', () => updateFileInput.click());
        updateDropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            updateDropZone.classList.add('dragover');
        });
        updateDropZone.addEventListener('dragleave', () => {
            updateDropZone.classList.remove('dragover');
        });
        updateDropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            updateDropZone.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                updateFileInput.files = e.dataTransfer.files;
                const reader = new FileReader();
                reader.onload = (e) => {
                    const preview = document.getElementById('updateImagePreview');
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                showToast('يرجى اختيار صورة صالحة', 'error');
            }
        });
        updateFileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            const preview = document.getElementById('updateImagePreview');
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.querySelector('img').src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('hidden');
                showToast('يرجى اختيار صورة صالحة', 'error');
            }
        });

        // Add loading state to form submissions
        document.getElementById('addCatalogForm').addEventListener('submit', (e) => {
            const submitButton = e.target.querySelector('button[type="submit"]');
            submitButton.classList.add('loading');
            submitButton.disabled = true;
        });
        document.getElementById('updateForm').addEventListener('submit', (e) => {
            const submitButton = e.target.querySelector('button[type="submit"]');
            submitButton.classList.add('loading');
            submitButton.disabled = true;
        });
        document.getElementById('deleteForm').addEventListener('submit', (e) => {
            const submitButton = e.target.querySelector('button[type="submit"]');
            submitButton.classList.add('loading');
            submitButton.disabled = true;
        });

        // Show toast for PHP messages
        <?php if ($message): ?>
            showToast('<?= htmlspecialchars($message) ?>', '<?= $message_type ?>');
        <?php endif; ?>

        // Animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes slideIn {
                from { opacity: 0; transform: translateX(20px); }
                to { opacity: 1; transform: translateX(0); }
            }
            .animate-fadeIn {
                animation: fadeIn 0.5s ease-out;
            }
            .animate-slideIn {
                animation: slideIn 0.5s ease-out;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>