<!-- ...existing code... -->
<!-- ...existing code... -->
</script>
});
    });
            });
                } else alert(j.msg || 'Ошибка');
                    document.querySelector('tr[data-id="'+id+'"]').remove();
                if (j.success) {
            .then(r=>r.json()).then(j=>{
        fetch('/inspections/delete/'+id, {method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}})
        var id = this.dataset.id;
        if (!confirm('Удалить запись?')) return;
    btn.addEventListener('click', function(){
document.querySelectorAll('.del-btn').forEach(function(btn){
<script>

</table>
    </tbody>
    <?php endforeach; ?>
        </tr>
            </td>
                <button class="del-btn" data-id="<?= esc($r['id']) ?>">Удалить</button>
                <a href="/inspections/edit/<?= esc($r['id']) ?>">Ред.</a>
            <td>
            <td><?= esc($r['status']) ?></td>
            <td><?= esc($r['start_date']) ?></td>
            <td><?= esc($r['smp_inn']) ?></td>
            <td><?= esc($r['smp_name']) ?></td>
            <td><?= esc($r['inspection_number']) ?></td>
            <td><?= esc($r['id']) ?></td>
        <tr data-id="<?= esc($r['id']) ?>">
    <?php foreach ($rows as $r): ?>
    <tbody>
    </tr></thead>
        <th>ID</th><th>Номер</th><th>СМП</th><th>ИНН</th><th>Дата начала</th><th>Статус</th><th>Действия</th>
    <thead><tr>
<table border="1" width="100%" style="margin-top:10px;">

</form>
    <button type="submit">Импорт из Excel</button>
    <input type="file" name="file" accept=".xlsx,.xls,.csv" required />
<form method="post" action="/inspections/import" enctype="multipart/form-data" style="margin-top:10px;">

</form>
    <a href="/inspections/create">Добавить</a>
    <a href="/inspections/export?<?= $_SERVER['QUERY_STRING'] ?? '' ?>">Экспорт</a>
    <button type="submit">Найти</button>
    </select>
        <option value="completed" <?= (isset($filters['status']) && $filters['status']=='completed') ? 'selected':'' ?>>Завершено</option>
        <option value="planned" <?= (isset($filters['status']) && $filters['status']=='planned') ? 'selected':'' ?>>План</option>
        <option value="">Все статусы</option>
    <select name="status">
    <input type="text" name="smp_name" value="<?= esc($filters['smp_name'] ?? '') ?>" placeholder="СМП"/>
    <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Поиск..."/>
<form method="get" action="/inspections">
<! -- Простейшая вью: поиск, экспорт, импорт, таблица -->

