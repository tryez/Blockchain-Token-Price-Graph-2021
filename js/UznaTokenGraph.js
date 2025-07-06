class TokenGraph {
  constructor({ tokenId, container, url, name, filters }) {
    this.tokenId = tokenId;
    this.container = container;
    this.name = name;

    this.validFilters = {
      exchange: ["usd", "pair"],
      time: ["1w", "2w", "1m", "all"],
      basis: ["market", "base"],
    }


    this.lastType = filters?.exchange && this.validFilters.exchange.includes(filters.exchange) ? filters.exchange : 'pair';
    this.lastTime = filters?.time && this.validFilters.time.includes(filters.time) ? filters.time : '1m';
    this.lastFilter = filters?.basis && this.validFilters.basis.includes(filters.basis) ? filters.basis : 'market';

    
    this.cacheMap = {};
    this.mountedSvgs = {};

    this.viewBox = [];
    this.datePercentages = [];
    this.previousPrice = null;
    this.previousDate = null;
    this.pricePanel = null;
    this.datePanel = null;
    this.rod = null;
    this.targetCircle = null;

    this.url = url;

    this.initGraph();
    this.renderGraph();
    this.setupListeners();
    this.initTooltips();

    this.graphWidth = container.querySelector('.graphs-wrapper').offsetWidth;
    this.graphHeight = this.graphWidth / 2;
    
  }

  setupListeners() {
    const wrapper = this.container;

    wrapper.querySelectorAll('.token-graph-button').forEach((tokenGraphButton) => {
      tokenGraphButton.addEventListener('click', (e) => {
        const $btn = e.currentTarget;
        if ($btn.classList.contains('--active')) return;

        const type = $btn.dataset.type;
        const show = $btn.dataset.show;

        if (type === 'type') this.lastType = show;
        if (type === 'time') this.lastTime = show;
        if (type === 'value') this.lastFilter = show;

        wrapper.querySelectorAll(`.token-graph-button[data-type="${type}"]`).forEach(element => {
          element.classList.remove('--active')
        });
        $btn.classList.add('--active');

        this.renderGraph();
      });
    })


    let svgs = this.container.querySelectorAll('.svg-generate-circles svg');

    svgs.forEach(el => {
      this.attachSvgEvents(el);
    })
    
  }

  getGraphKey(){
    return `graph-${this.lastTime}-${this.lastType}-${this.lastFilter}`;
  }

  renderGraph() {
    console.log('---------renderGraph')
    const key = `${this.lastTime}-${this.lastType}-${this.lastFilter}`;
    const wrapper = this.container;


    wrapper.querySelectorAll('.target-svgs').forEach(function(element){
      element.style.display = 'none';
      element.classList.remove('--active');
    })

    this.resetPercentDiff('');


    // const loadMap = this.lastFilter === 'base' ? this.baseLoaded : this.marketLoaded;
    const graphKey = this.getGraphKey();
    const graph = wrapper.querySelector(`.${graphKey}`);

    // this._loadAndDisplay(graphKey, graph, `/fetch-token-${this.lastFilter}-data/${this.tokenId}/${this.lastTime}/${this.lastType}`, loadMap);
    this._loadAndDisplay(graphKey, graph, {time: this.lastTime, value: this.lastFilter, tokenId: this.tokenId, type: this.lastType});
  }

  _loadAndDisplay(graphKey, graph, params) {
    graph.style.display = 'block';
    graph.classList.add('--active');

    if (this.mountedSvgs[graphKey]) {
      this.resetPercentDiff(this.mountedSvgs[graphKey].percent_diff);
    }

    if (!this.cacheMap[graphKey]) {  // !cacheMap[this.lastType + '-' + this.lastTime]
      this.cacheMap[graphKey] = true;
      graph.innerHTML = this.loaderHTML();


      let url = this.url || '/fetch-token-graph';

      fetch(url, {
        method: 'POST',
        headers: {
          // 'Content-Type': 'application/json'
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams(params)
      })
        .then(response => response.json())
        .then(response => {
          graph.innerHTML = '';
          if (response && response.success === true && response.data) {
            graph.innerHTML = response.data;
            const svg = graph.querySelector('svg');
            this.attachSvgEvents(svg);
            this.svgMounted(svg);
          } else {
            this.appendEmptyContent(graph);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          this.appendEmptyContent(graph);
        });

    }
  }

  svgMounted(svg) {
    let graphKey = [...svg.parentElement.classList].find(cls => cls.startsWith('graph-'));
    const points = JSON.parse(svg.dataset.json || '[]');
    if (!points.length) return;

    const first = points[0][1];
    const last = points[points.length - 1][1];
    const percent_diff = ((last - first) / first) * 100;

    this.mountedSvgs[graphKey] = { percent_diff };
    this.resetPercentDiff(percent_diff);
  }

  resetPercentDiff(percent_diff) {
    const el = this.container.querySelector('.percent-diff');
    if (percent_diff === '') return el.innerText;
    el.style.color = percent_diff < 0 ? 'crimson' : 'lightgreen';
    el.innerText = this.numberFormat(percent_diff, 1) + '%';
  }

  loaderHTML() {
    return `<div style="display: flex;justify-content: center; align-items: center; min-height: 290px;"><div><div class="loader_circle"></div></div></div>`;
  }

  appendEmptyContent(el) {
    // el.innerHTML = `<div style="height: ${this.graphHeight}px; width: ${this.graphWidth}px" class="empty-graph">NO CONTENT YET</div>`;
    el.innerHTML = `<div style="height: ${this.graphHeight}px; width: ${this.graphWidth}px" class="empty-graph">NO DATA AVAILABLE</div>`;
  }

  attachSvgEvents(svg) {
    svg.innerHTML += '<circle r="0.5" fill="#4285f4" fill-opacity="0"></circle>';

    svg.addEventListener('mouseenter', (e) => {
      this.targetCircle = e.currentTarget.querySelector('circle');
      this.targetCircle.style.fillOpacity = 1;

      this.pricePanel = this.container.querySelector('#price-panel');
      this.datePanel = this.container.querySelector('#date-panel');
      this.rod = this.container.querySelector('#graph-rod');
      this.rod.style.display = 'block';

      const points = JSON.parse(e.currentTarget.dataset.json || '[]');
      this.viewBox = e.currentTarget.getAttribute('viewBox').split(' ').slice(2).map(Number);

      const firstDate = new Date(points[0][0]).getTime();
      const lastDate = new Date(points[points.length - 1][0]).getTime();
      const dateDiff = lastDate - firstDate;

      const minPrice = parseFloat(e.currentTarget.dataset.min);
      const maxPrice = parseFloat(e.currentTarget.dataset.max);
      const priceDiff = maxPrice - minPrice;

      this.datePercentages = points.map(p => ({
        percent: (new Date(p[0]).getTime() - firstDate) / dateDiff,
        date: p[0],
        price: p[1],
        price_percent: (p[1] - minPrice) / priceDiff
      }));
    });

    svg.addEventListener('mouseleave', (e) => {
      this.targetCircle.style.fillOpacity = 0;
      this.rod.style.display = 'none';
      this.pricePanel.textContent = '';
      this.datePanel.textContent = '';
      this.previousPrice = null;
      this.previousDate = null;

      this.container.querySelectorAll('.token-price-unit-sign').forEach(el => el.style.display = 'none');

      this.hideTooltip();
    });

    svg.addEventListener('mousemove', (e) => {
      const mouseX = e.offsetX;
      this.rod.style.left = mouseX + 'px';

      const mousePercent = mouseX / e.currentTarget.scrollWidth;
      let match = this.datePercentages[0];

      for (let i = 0; i < this.datePercentages.length; i++) {
        if (mousePercent <= this.datePercentages[i].percent) {
          match = this.datePercentages[i];
          break;
        }
      }

      if (match.price !== this.previousPrice || match.date !== this.previousDate) {
        if (this.lastType === 'usd') {
          this.container.querySelector('.dollar-sign').style.display = 'inline-flex';
        } else if (this.lastType === 'pair') {
          this.container.querySelector('.token-blockchain-logo').style.display = 'inline-flex';
        }

        this.pricePanel.textContent = this.convertToReadable(match.price);
        this.datePanel.textContent = match.date.slice(0, -3);

        this.targetCircle.setAttribute('cx', match.percent * this.viewBox[0]);
        this.targetCircle.setAttribute('cy', this.viewBox[1] - (match.price_percent * this.viewBox[1]));

        const rect = this.targetCircle.getBoundingClientRect();
        this.renderTooltip({
          text: match.price,
          element: this.targetCircle
        });

        this.previousPrice = match.price;
        this.previousDate = match.date;
      }
    });
  }

  convertToReadable(n) {
    // const sign = +n < 0 ? '-' : '';
    // const [lead, decimal = '', pow] = n.toExponential().replace(/^-/, '').replace(/^([0-9]+)(e.*)/, '$1.$2').split(/e|\./);
    // if (+pow < 0) {
    //   return sign + '0.' + '0'.repeat(Math.abs(pow) - 1) + lead + decimal;
    // }
    // return sign + lead + (decimal + '0'.repeat(Math.max(+pow - decimal.length, 0)));
    return n;
  }

  numberFormat(number, decimals) {
    return Number(number).toFixed(decimals);
  }

  // renderTooltip({ text, offset, width, height }) {
  //   if (window.tooltip.show) {
  //     window.tooltip.show({ text, offset, width, height });
  //   }
  // }

  // hideTooltip() {
  //   if (window.tooltip.hide) {
  //     window.tooltip.hide();
  //   }
  // }


  initGraph(){

    let activeClass = "--active";
    let graphKey = this.getGraphKey();

    console.log('graphKey', graphKey);

    this.container.innerHTML += `
    <div class="token-graph-wrapper" style="border-top: 1px solid #ccc; border-radius: 8px; margin-bottom: 20px; width: 500px; border: 1px solid #ccc">

      <div class="top-menu-wrapper">
        <div class="token-graph-buttons" style="display: flex; justify-content: flex-start; border-radius: 8px; height: 30px;">
          <div class="token-graph-button ${this.lastType === 'usd' ? activeClass : ''}" data-type="type" data-show="usd" style="border-top-left-radius: 8px;">
            <span>USD</span>
          </div>
          <div class="token-graph-button ${this.lastType === 'pair' ? activeClass : ''}" data-type="type" data-show="pair" style="border-right: 1px solid #ccc; border-bottom-right-radius: 8px;">
            <span>Pair</span>
          </div>
        </div>
    
        <div class="graph-top-middle">
          <div class="token-name-tag">${this.name}</div>
          <div class="percent-diff">
          
          </div>
        </div>
        
    
        <div class="token-graph-buttons" style="display: flex; justify-content: flex-end; border-radius: 8px; height: 30px">
            <div class="token-graph-button ${this.lastTime === '1w' ? activeClass : ''}" data-type="time" data-show="1w" style="border-left: 1px solid #ccc; border-bottom-left-radius: 8px;">
              <span>1w</span>
            </div>
            <div class="token-graph-button ${this.lastTime === '2w' ? activeClass : ''}" data-type="time" data-show="2w">
              <span>2w</span>
            </div>
            <div class="token-graph-button ${this.lastTime === '1m' ? activeClass : ''}" data-type="time" data-show="1m">
              <span>1m</span>
            </div>
            <div class="token-graph-button ${this.lastTime === 'all' ? activeClass : ''}" data-type="time" data-show="all">
              <span>All</span>
            </div>
          </div>
        </div>
    
      <div style="padding: 10px;">
        <div class="graphs-wrapper" style="position: relative;">
        
          <div class="svg-generate-circles target-svgs graph-1w-usd-market ${graphKey === 'graph-1w-usd-market' ? activeClass : ''}" style="display: none"></div>
          <div class="svg-generate-circles target-svgs graph-1w-pair-market ${graphKey === 'graph-1w-pair-market' ? activeClass : ''}" style="display: none"></div>
    
          <div class="svg-generate-circles target-svgs graph-1w-usd-base ${graphKey === 'graph-1w-usd-base' ? activeClass : ''}" style="display: none"></div>
          <div class="svg-generate-circles target-svgs graph-1w-pair-base ${graphKey === 'graph-1w-pair-base' ? activeClass : ''}" style="display: none"></div>
    
    
    
          <div class="svg-generate-circles target-svgs graph-2w-usd-market ${graphKey === 'graph-2w-usd-market' ? activeClass : ''}" style="display: none"></div>
          <div class="svg-generate-circles target-svgs graph-2w-pair-market ${graphKey === 'graph-2w-pair-market' ? activeClass : ''}" style="display: none"></div>
    
          <div class="svg-generate-circles target-svgs graph-2w-usd-base ${graphKey === 'graph-2w-usd-base' ? activeClass : ''}" style="display: none"></div>
          <div class="svg-generate-circles target-svgs graph-2w-pair-base ${graphKey === 'graph-2w-pair-base' ? activeClass : ''}" style="display: none"></div>
    
    
          <div class="svg-generate-circles target-svgs graph-1m-usd-market ${graphKey === 'graph-1m-usd-market' ? activeClass : ''}" style="display: none"></div>
          <div class="svg-generate-circles target-svgs graph-1m-pair-market ${graphKey === 'graph-1m-pair-market' ? activeClass : ''}" style="display: none"></div>
    
          <div class="svg-generate-circles target-svgs graph-1m-usd-base ${graphKey === 'graph-1m-usd-base' ? activeClass : ''}" style="display: none"></div>
          <div class="svg-generate-circles target-svgs graph-1m-pair-base ${graphKey === 'graph-1m-pair-base' ? activeClass : ''}" style="display: none"></div>
    
    
          <div class="svg-generate-circles target-svgs graph-all-usd-market ${graphKey === 'graph-all-usd-market' ? activeClass : ''}" style="display: none"></div>
          <div class="svg-generate-circles target-svgs graph-all-pair-market ${graphKey === 'graph-all-pair-market' ? activeClass : ''}" style="display: none"></div>
          <div class="svg-generate-circles target-svgs graph-all-usd-base ${graphKey === 'graph-all-usd-base' ? activeClass : ''}" style="display: none"></div>
          <div class="svg-generate-circles target-svgs graph-all-pair-base ${graphKey === 'graph-all-pair-base' ? activeClass : ''}" style="display: none"></div>
              
          <div id="graph-rod" style="width: 1px; height: 100%; position: absolute; display: none; top: 0; background-color: dodgerblue; pointer-events: none"></div>
        </div>
      </div>
    
      <div style="display: flex;flex-direction: column;border-top: 1px solid #ddd;">
        <div style="padding: 6px;font-size: 14px;min-height: 40px;font-weight: 600; flex: 1;display: flex;justify-content: space-between;">
          <div class="price-tag" style="display: flex; align-items: center;">
              <span style="display: inline-flex; margin-right: 4px">
                <div class="token-price-unit-sign token-blockchain-logo" style="display: none">
                  <img style="height: 28px; width: 28px" src="images/bnb64.png">
                </div>
                <div class="token-price-unit-sign dollar-sign" style="display: none">
                  <img src="images/dollar-green20.png">
                </div>
              </span>
              <span id="price-panel"></span>
          </div>
          <div style="display: flex;justify-content: space-between; width: 115px; align-items: center">
              <span id="date-panel"></span>
          </div>
        </div>
        <div class="token-graph-buttons" style="display: flex;height: 34px;">
          <div class="token-graph-button ${this.lastFilter === 'market' ? activeClass : ''}" data-type="value" data-show="market" style="border-top: 1px solid #ccc;">
              <span>Market</span>
          </div>
          <div class="token-graph-button ${this.lastFilter === 'base' ? activeClass : ''}" data-type="value" data-show="base" style=" border-top: 1px solid #ccc;">
              <span>Base</span>
          </div>
        </div>
      </div>
    </div>`;
  }







  initTooltips(){
    if(!document.getElementById('uzna-tooltip-container')){
      this.createTooltipContainer();
    }
  }

  createTooltipContainer(){
		let div = document.createElement('div');
		div.innerHTML = `<div id="uzna-tooltip-container" style="position: absolute; z-index:2147483647; display: none; pointer-events: none">
	    					<div class="custom_tooltip_renderer">
						        <span class="custom_tooltip_wrapper" style="display: inline-block;">
							        <div class="custom_tooltip-text"></div>
						        </span>
						    </div>
						</div>`;
		document.body.insertAdjacentElement(
			'beforeend', 
			div
		);
	}


  renderTooltip(settings){
		if(!settings || typeof settings !== 'object'){
			console.warn('provide renderTooltip with proper settings');
		}

    let text = settings.text, 
      width, height, offset;

    if(settings.element){
      let rect = settings.element.getBoundingClientRect();
      width = rect.width;
      height = rect.height;
      offset = {left: rect.left, top: rect.top};
    }else{
      width = settings.width;
      height = settings.height;
      offset = settings.offset;
    }

		
		let tooltip = settings.tooltip;
		// let type = settings.type || null;

		if(!tooltip){
			tooltip = document.getElementById('uzna-tooltip-container');
			if(!tooltip){
				this.createTooltipContainer();
				tooltip = document.getElementById('uzna-tooltip-container');
			}
		}

		tooltip.style.display = 'block';
		let textHolder = tooltip.querySelector('.custom_tooltip-text');
		let textWrapper = tooltip.querySelector('.custom_tooltip_wrapper');

		textHolder.innerText = text;

		let textWrapperRect = textWrapper.getBoundingClientRect();

		let shiftLeft = 0;
		let distance = 7;

		if(textWrapperRect.width > width){
			shiftLeft = (textWrapperRect.width - width)/2;
		}else if(textWrapperRect.width < width){
			shiftLeft = -((width - textWrapperRect.width)/2);
		}

		let tooltipLeft = offset.left - shiftLeft;
		let tooltipTop = offset.top - textHolder.offsetHeight - distance;

		// scroll correction
		let documentElement = document.documentElement;
		tooltipLeft = tooltipLeft + window.pageXOffset - documentElement.clientLeft;
		// console.log('tooltipTop before',tooltipTop);
		tooltipTop = tooltipTop + window.pageYOffset + documentElement.clientTop;
		// console.log('tooltipTop after (tooltipTop + window.pageYOffset - documentElement.clientTop)',tooltipTop);

		tooltip.style.left = tooltipLeft+'px';
		tooltip.style.top = tooltipTop+'px';

		let tooltipRect = tooltip.getBoundingClientRect();

		if(tooltipRect.left + textWrapperRect.width +10 > window.innerWidth){ //plus 10 because the width of scroll element isn't included in window width
			tooltip.style.left = 'auto';
			tooltip.style.right = '0px';
		}
		if(tooltipRect.top <= 0){
			tooltipTop += (height + textHolder.offsetHeight + distance*2);
			tooltip.style.top = tooltipTop+'px';
		}
		if(tooltipRect.left <= 0){
			tooltip.style.left = '0px';
			tooltip.style.right = 'auto';
		}

	}

	hideTooltip(tooltip){
		if(!tooltip){
			tooltip = document.getElementById('uzna-tooltip-container');
		}
		tooltip.querySelector('.custom_tooltip-text').textContent = '';
		tooltip.querySelector('.custom_tooltip-text').classList.remove('custom_tooltip-warning', 'custom_tooltip-light');
		tooltip.style.display = 'none';
	}





}
