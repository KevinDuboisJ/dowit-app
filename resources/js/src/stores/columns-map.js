const columnsMap = {
  name: {
    name: 'Naam'
  },
  campus_id: {
    name: 'Campus'
  },
  link: {
    name: 'Link'
  },
  department_ps_number: {
    name: 'Afdeling'
  },
  SpcFloorNettM2: {
    name: 'Netto M²'
  },
  aircon: {
    name: 'Airco'
  },
  SpcRecStatus: {
    name: 'Status'
  },
  _spccode: {
    name: 'Space code'
  },
  SpcRecCreateDate: {
    name: 'Aangemaakt op'
  },
  content: {
    name: 'Content'
  }
}

function getColumnName(key) {
  return columnsMap.hasOwnProperty(key) ? columnsMap[key]['name'] : 'undefined'
}

export { getColumnName }
