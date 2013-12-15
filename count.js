db.decks_new.distinct('name').forEach(function(name){
    var count = db.decks_new.find({name: name}).count()
    if(count >= 1000){
        print(name + ': ' +  count);
    }
})