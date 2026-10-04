</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function changeQty(id, delta) {
  const input = document.getElementById('qty-' + id);
  let val = parseInt(input.value || 1);
  val = Math.max(1, val + delta);
  input.value = val;
}
</script>

</body>
</html>
</body>
</html>
