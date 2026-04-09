

export function initializeSelect() {
  const selectList = []
  for (const brSelect of window.document.querySelectorAll('.br-select')) {
    const brselect = new (window as any).core.BRSelect('br-select', brSelect)
    //Exemplo de uso de listener do select
    brSelect.addEventListener('onChange', function () { })
    selectList.push(brselect)
  }
}