getParameterByName = (name)->
  params = window.location.search.substr(1).split("&")
  for param in params
    p = param.split('=')
    return p[1] if p[0] == name
  return ''

window.delayRedirectToModule = ->
  setTimeout ->
   location.reload()
  , 10000

path = [location.protocol, '//', location.host, location.pathname].join('');
if window.location != path
  if getParameterByName('remove') == '1'
    window.location.href = path
  if getParameterByName('cb_return_status') && getParameterByName('cb_return_status') != 'success'
    setTimeout ->
      window.location.href = path
    , 3000