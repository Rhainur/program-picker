var qData = null; // THIS VARIABLE HOLDS THE DATA ;)

$(document).ready( function(){
	window.onpopstate = function(){
		displayQuestion(null);
	};
	$('.outer-container').css('min-height', $(window).height()-130);
	$.get('data.json', null, function(data){
		qData = data;
		displayQuestion(null);
	}, "json");
});

function displayQuestion(routeString){
	$('.stack-container,.question-container,.choice-container,.answer-container').empty();

	var invalidFlag = false;
	var route=[];
	var i;
	if(routeString===null){
		routeString='';
		if(window.location.hash.length>1){
			route=window.location.hash.substring(1).split('/');
			for(i in route){
				routeString += route[i] + '/';
			}
		}
	}else{
		route = routeString.split('/');
	}

	if(routeString.length > 0 && routeString[routeString.length-1]!='/')
		routeString+='/';
	
	var currentNode = qData;
	var stackHTML=$('<div />'),stackNode=null;

	for(i in route){
		if(route[i].length===0)
			break;
		if(typeof(currentNode.children)==='undefined'||currentNode.children.length===0){
			invalidFlag=true;
			break;
		}

		stackHTML.append('<div class="arrow-down"></div>').append($('<div />').addClass('stack-piece').html('<a href="#'+getRouteString(route,i)+'">'+currentNode.choices[route[i]]+'</a>'));
		currentNode = currentNode.children[route[i]];
	}

	$('.stack-container').html(stackHTML);

	if(currentNode.answer!==null){
		$('.answer-container').html(currentNode.answer);
	}else{

		$('.question-container').html(currentNode.question);
		var outputHTML='', loopEnd = currentNode.choices.length;

		for(i in currentNode.choices){
			outputHTML += '<li><a href="#'+routeString+i+'">'+currentNode.choices[i]+'</a></li>';
		}
		$('.choice-container').html(outputHTML);
	}

	$('.choice-container a,.stack-container a').off().click(function(){
		displayQuestion($(this).attr('href').substring(1));
		$('html,body').animate({scrollTop: $('.stack-container').position().top});
	});
}

function getRouteString(route,depth){
	var s = '';
	for(var i=0;i<depth;i++){
		if(i!==0)
			s += '/';
		s += route[i];
	}
	return s;
}