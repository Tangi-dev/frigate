<!-- ...existing code... -->
<form method="post" action="/inspections/save">
    <input type="hidden" name="id" value="<?= esc($item['id'] ?? '') ?>">
    <label>СМП</label>
    <input type="hidden" id="smp_id" name="smp_id" value="<?= esc($item['smp_id'] ?? '') ?>">
    <input type="text" id="smp_text" value="<?= esc($item['smp_name'] ?? '') ?>" placeholder="Введите название СМП"/>
    <button type="button" id="smp_search_btn">Поиск</button>
    <div id="smp_results"></div>

    <label>Номер проверки</label>
    <input type="text" name="inspection_number" value="<?= esc($item['inspection_number'] ?? '') ?>" required />

    <label>Контролирующий орган</label>
    <input type="text" name="controlling_authority" value="<?= esc($item['controlling_authority'] ?? '') ?>" />

    <label>Дата начала</label>
    <input type="date" name="start_date" value="<?= esc($item['start_date'] ?? '') ?>" />

    <label>Дата окончания</label>
    <input type="date" name="end_date" value="<?= esc($item['end_date'] ?? '') ?>" />

    <label>Статус</label>
    <input type="text" name="status" value="<?= esc($item['status'] ?? '') ?>" />

    <label>Примечание</label>
    <textarea name="notes"><?= esc($item['notes'] ?? '') ?></textarea>

    <button type="submit">Сохранить</button>
</form>

<script>
document.getElementById('smp_search_btn').addEventListener('click', function(){
    var q = document.getElementById('smp_text').value;
    fetch('/api/smp/search?q='+encodeURIComponent(q))
        .then(r=>r.json()).then(j=>{
            var html = '';
            if (j.results && j.results.length) {
                j.results.forEach(function(it){
                    html += '<div class="smp-item" data-id="'+it.id+'">'+it.text+'</div>';
                });
            } else html = 'Ничего не найдено';
            document.getElementById('smp_results').innerHTML = html;
            document.querySelectorAll('.smp-item').forEach(function(el){
                el.addEventListener('click', function(){
                    document.getElementById('smp_id').value = this.dataset.id;
                    document.getElementById('smp_text').value = this.textContent;
                    document.getElementById('smp_results').innerHTML = '';
                });
            });
        });
});
</script>
<!-- ...existing code... -->

