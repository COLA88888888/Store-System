<?php $w = (int)($_GET['w'] ?? 1200); $go = !empty($_GET['go']); ?><body style="margin:0;background:#888"><iframe id="a" src="/websizewlaodev/pages/contact" style="width:<?php echo $w; ?>px;height:700px;border:0;background:#fff"></iframe>
<script>document.getElementById('a').onload=function(){var d=this.contentDocument,w=this.contentWindow;
setTimeout(function(){var c=d.createElement('canvas');c.width=640;c.height=420;var x=c.getContext('2d');var g=x.createLinearGradient(0,0,640,420);g.addColorStop(0,'#1d4ed8');g.addColorStop(1,'#22c55e');x.fillStyle=g;x.fillRect(0,0,640,420);x.fillStyle='#fff';x.font='bold 64px sans-serif';x.fillText('TEST IMG',150,230);
c.toBlob(function(b){var dt=new w.DataTransfer();dt.items.add(new w.File([b],'photo.png',{type:'image/png'}));dt.items.add(new w.File(['%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF'],'quote-test.pdf',{type:'application/pdf'}));
var inp=d.querySelector('[data-chat-file]');inp.files=dt.files;inp.dispatchEvent(new w.Event('change',{bubbles:true}));
<?php if ($go): ?>setTimeout(function(){d.querySelector('.ccf-send').click();},800);<?php endif; ?>},'image/png');},1800);};</script>
