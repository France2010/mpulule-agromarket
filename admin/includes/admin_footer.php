</div></div></div>
<script>
function showToast(msg,type){
    const t=document.getElementById('toast');
    const m=document.getElementById('toast-msg');
    if(!t||!m)return;
    m.textContent=msg;
    t.style.background=type==='error'?'#7f1d1d':'var(--dark)';
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),3200);
}
function confirmAction(msg){return confirm(msg||'Are you sure?');}
</script>
</body></html>
