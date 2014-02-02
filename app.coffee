settings = require './config.json'

mycard = require 'mycard-sdk'

restify = require 'restify'
fs = require 'fs'
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
MongoClient.connect settings['db_server'], settings['db_options'], (err, db)->
  console.log 'nyanpass'

  #decks list
  decks =
    list: (req, res, next) ->
      if !req.legacy_decksync_compatible and req.username != req.params.user
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
            if req.legacy_decksync_compatible
              deck['cards'] = deck['card_usages']
              delete deck['card_usages']
          res.send(decks)
          next();

    show: (req,res,next)->
      if !req.legacy_decksync_compatible and req.username != req.params.user
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
            res.send(410);
            return next(false)


      next()

    update: (req, res, next) ->
      if !req.legacy_decksync_compatible and req.username != req.params.user
        res.header('WWW-Authenticate', 'Basic realm="MyCard API"');
        res.send(401);
        return next(false)

      return next(new restify.MissingParameterError()) unless req.query['cards']
      db.collection('users').findOne name: req.params.user, (err, user)->
        return next(err) if err
        return next(new restify.ResourceNotFoundError()) unless user?
        db.collection('decks_new').findOne user: user['_id'], name: req.params.name, (err, deck)->
          return next(err) if err
          if req.query['updated_at']
            updated_at = new Date(req.query['updated_at'])
            now = new Date()
            updated_at = now if updated_at > now
          else
            updated_at = new Date()
          card_usages = mycard.card_usages_decode(req.query['cards']);
          if deck?
            if deck['deleted'] or deck['updated_at'] <= updated_at
              if mycard.card_usages_equal(deck['card_usages'], card_usages)
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
                  }, (err)->
                    return next(err) if err
                    res.setHeader('Location', "https://my-card.in/decks/#{req.params.user}/#{req.params.name}.json");
                    res.send(201)
                    next()
            else
              return next(new restify.ConflictError())
          else
            db.collection('decks_new').insert
              name: req.params.name,
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
                res.setHeader('Location', "https://my-card.in/decks/#{req.params.user}/#{req.params.name}.json");
                res.send(201)
                next()

    delete: (req, res, next) ->
      if !req.legacy_decksync_compatible and req.username != req.params.user
        res.header('WWW-Authenticate', 'Basic realm="MyCard API"');
        res.send(401);
        return next(false)

      db.collection('users').findOne name: req.params.user, (err, user)->
        return next(err) if err
        return next(new restify.ResourceNotFoundError()) unless user?
        db.collection('decks_new').findOne user: user['_id'], name: req.params.name, (err, deck)->
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
            , (err)->
              return next(err) if err
              res.send 204
              next()

    legacy_decksync_compatible: (req, res, next) ->
      req.legacy_decksync_compatible = true
      index = req.params.user.indexOf('@')
      req.params.user = req.params.user.slice(0, index) if index != -1

  limited =
    list: (req, res, next) ->
      fs.readFile 'lflist.conf', encoding: 'utf8', (err, data)->
        result = []
        last = null
        for line in data.split("\n")
          switch line[0]
            when '#', undefined, null
              null
            when '!'
              result.push last if last
              last = id: line.slice(1), cards: []
            else
              [card_id, count] = line.split(" ")
              last.cards.push card_id: card_id, count: count
        result.push last if last
        res.send(result)
        next()

    list_conf: (req, res, next) ->
      fs.readFile 'lflist.conf', encoding: 'utf8', (err, data)->
        return next(err) if err
        res.setHeader('content-type', 'text/plain');
        res.send(data)
        next()

  #legacy_decksync_compatible
  server.get /\/decks\//, (req, res, next)->
    decks.legacy_decksync_compatible(req, res, next)
    decks.list(req, res, next)
  server.put /\/decks\//, (req, res, next)->
    decks.legacy_decksync_compatible(req, res, next)
    decks.update(req, res, next)
  server.del /\/decks\//, (req, res, next)->
    decks.legacy_decksync_compatible(req, res, next)
    decks.delete(req, res, next)

  #route
  server.get '/decks/:user', decks.list
  server.get '/decks/:user/:name', decks.show
  server.put "/decks/:user/:name", decks.update
  server.del "/decks/:user/:name", decks.delete

  server.get '/limited', limited.list
  server.get '/limited.json', limited.list
  server.get '/limited.conf', limited.list_conf

  server.listen(9004);