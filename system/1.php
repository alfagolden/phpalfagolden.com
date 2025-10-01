<!doctype html><meta charset="utf-8">
<style>
  body{
    margin:0;
    font-family:'Cairo',sans-serif;
    background:#f8f9fa;
    direction:rtl;
    display:flex;
    justify-content:center;
    padding:40px 20px; /* مسافة من الأعلى والأسفل */
  }
  table{
    border-collapse:collapse;
    min-width:400px;
    background:#fff;
    box-shadow:0 4px 12px rgba(0,0,0,.1);
  }
  th,td{
    padding:12px 20px;
    border-bottom:1px solid #e5e7eb;
    text-align:right
  }
  th{
    background:#f1f3f5;
    font-weight:600
  }
  tr:hover{
    background:#f8f9ff;
    cursor:pointer
  }
  #msg{
    position:fixed;
    top:20px;
    right:20px;
    background:#00b894;
    color:#fff;
    padding:10px 16px;
    border-radius:8px;
    font-weight:bold;
    opacity:0;
    transition:.3s
  }
  #msg.show{opacity:1}
</style>

<table id="t">
  <tr><th>الاسم</th><th>الرمز</th></tr>
</table>
<div id="msg">✅ تم النسخ</div>

<script>
const URL='https://base.alfagolden.com/api/database/fields/table/704/';
const HDR={Authorization:'Token h5qAt85gtiJDAzpH51WrXPywhmnhrPWy',Accept:'application/json'};
const pad=id=>`{{${String(+id).padStart(Math.max(4,String(+id).length),'0')}}}`;

function showMsg(){
  msg.classList.add('show');
  setTimeout(()=>msg.classList.remove('show'),1500);
}

fetch(URL,{headers:HDR}).then(r=>r.json()).then(d=>{
  (Array.isArray(d)?d:d.results||[]).forEach(f=>{
    const code=pad(f.id);
    const tr=document.createElement('tr');
    tr.innerHTML=`<td>${f.name||''}</td><td>${code}</td>`;
    tr.onclick=()=>{
      navigator.clipboard.writeText(code).then(showMsg);
    };
    t.appendChild(tr);
  });
});
</script>
