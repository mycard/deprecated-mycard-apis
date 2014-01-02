restify = require('restify');
MongoClient = require('mongodb').MongoClient;
server = restify.createServer
  name: 'mycard-apis'
server.use restify.acceptParser(server.acceptable)
server.use restify.authorizationParser()
server.use restify.dateParser()
server.use restify.queryParser()
server.use restify.jsonp()
server.use restify.gzipResponse()
server.use restify.bodyParser()

server.use restify.conditionalRequest()


#因为是个回调...WTF要怎么分文件..先暂时全都合并到一个文件里了
MongoClient.connect "mongodb://live.my-card.in:27017,master.my-card.in:27017/mycard?readPreference=nearest&replicaSet=mycard", {server:{auto_reconnect:true,poolSize: 5}}, (err, db)->

  #decks list
  server.get '/decks/:user', (req, res, next) ->
    if req.username != req.params.user
      res.header('WWW-Authenticate', 'Basic realm="MyCard API"');
      res.send(401);
      return next(false)

    db.collection('users').findOne name: req.params.user, (err, user)->
      return next(err) if err
      return next(new restify.ResourceNotFoundError()) unless user?
      db.collection('decks_new').find(user: user['_id'], deleted: {$ne: true}).toArray (err, decks)->
        return next(err) if err
        for deck in decks
          deck['user'] = user
          if req.query['legacy_decksync_compatible']
            deck['cards'] = deck['card_usages']
            delete deck['card_usages']
        res.send(decks)
        next();

  #decks show
  server.get '/decks/:user/:name', (req, res, next) ->
    if req.username != req.params.user
      res.header('WWW-Authenticate', 'Basic realm="MyCard API"');
      res.send(401);
      return next(false)

    db.collection('users').findOne name: req.params.user, (err, user)->
      ###return next(err) if err
      return next(new restify.ResourceNotFoundError()) unless user?
      db.collection('decks_new').find user: user['_id'], name: req.params.name, (err, deck)->
        return next(err) if err
        return next(new restify.ResourceNotFoundError()) unless deck?
        if deck['deleted']
          res.send(410);
          return next(false)


    next()

###

  #decks update
  server.put "/decks/:user/:name", (req, res, next) ->
    if req.username != req.params.user
      res.header('WWW-Authenticate', 'Basic realm="MyCard API"');
      res.send(401);
      return next(false)

    db.collection('users').findOne name: req.params.user, (err, user)->
      return next(err) if err
      return next(new restify.ResourceNotFoundError()) unless user?
      db.collection('decks_new').find user: user['_id'], name: req.params.name, (err, deck)->
        return next(err) if err
        updated_at = new Date(req.query['updated_at'])
        card_usages = null #MyCard::decode(cards);
        if deck?
          if deck['deleted'] or deck['updated_at'] <= updated_at
            if deck['card_usages'] == card_usages
              db.collection('decks_new').update {
                _id: deck['_id']
              }, {
                $set:
                  updated_at: updated_at
                $unset:
                  deleted: ''
              }, (err)->
                return next(err) if err
                res.send(204)
                next()
            else
              db.collection('deck_versions').insert
                deck: deck['_id'],
                card_usages: card_usages,
                version: deck['version'] + 1,
                created_at: updated_at
              , (err, docs)->
                return next(err) if err
                db.collection('decks_new').update {
                  _id: deck['_id']
                }, {
                  $set:
                    updated_at: updated_at,
                    card_usages: card_usages,
                    version: deck['version'] + 1,
                  $unset:
                    deleted: ''
                }, (err, docs)->
                  return next(err) if err
                  res.setHeader('Location', "https://my-card.in/decks/#{req.param.user}/#{req.param.name}.json");
                  res.send(201)
                  next()
          else
            return next(new restify.ConflictError())
        else
          db.collection('decks_new').insert
            name: req.param.name,
            user: user['_id'],
            created_at: updated_at,
            updated_at: updated_at,
            card_usages: card_usages,
            version: 1
          , (err, deck)->
            return next(err) if err
            db.collection('deck_versions').insert
              deck: deck['_id'],
              card_usages: card_usages,
              version: 1,
              created_at: updated_at
            , (err)->
              return next(err) if err
              res.setHeader('Location', "https://my-card.in/decks/#{req.param.user}/#{req.param.name}.json");
              res.send(201)
              next()

  #decks delete
  server.del "/decks/:user/:name", (req, res, next) ->
    if req.username != req.params.user
      res.header('WWW-Authenticate', 'Basic realm="MyCard API"');
      res.send(401);
      return next(false)

    db.collection('users').findOne name: req.params.user, (err, user)->
      return next(err) if err
      return next(new restify.ResourceNotFoundError()) unless user?
      db.collection('decks_new').find user: user['_id'], name: req.params.name, (err, deck)->
        return next(err) if err
        return next(new restify.ResourceNotFoundError()) unless deck?
        if deck['deleted']
          res.send 410
          next()
        else
          db.collection('decks_new').update {
            _id: deck['_id']
          }, {
            $set:
              updated_at: new Date()
              deleted: true
          }
          res.send 204
          next()


server.listen(9004);