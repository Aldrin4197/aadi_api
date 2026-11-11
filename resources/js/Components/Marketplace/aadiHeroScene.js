import * as THREE from 'three';

// These are AADI's existing API keys; the renderer keeps its own descriptive pose names.
export const HERO_CATEGORY_MAP = Object.freeze({ spaces: 'spaces', haul: 'vehicles', raketeer: 'rakets' });
export function resolveHeroCategory(value) {
  return HERO_CATEGORY_MAP[value] || (['spaces', 'vehicles', 'rakets'].includes(value) ? value : 'spaces');
}

export function createAadiHeroScene({canvas, category='spaces', onMotionChange=()=>{}, onError=()=>{}}) {
  if(!canvas)throw new Error('A canvas element is required.');
  let renderer=null,scene=null,frameId=0,destroyed=false,suspended=false;
  const cleanups=[];
  function listen(target,type,handler,options){
    target.addEventListener(type,handler,options);
    cleanups.push(()=>target.removeEventListener(type,handler,options));
  }
  function dispose(){
    if(destroyed)return;destroyed=true;
    cancelAnimationFrame(frameId);
    for(const cleanup of cleanups.splice(0))cleanup();
    canvas.classList.remove('is-dragging');
    const geometries=new Set(),materials=new Set();
    scene?.traverse(object=>{
      if(object.geometry)geometries.add(object.geometry);
      if(object.material)(Array.isArray(object.material)?object.material:[object.material]).forEach(m=>materials.add(m));
      object.shadow?.dispose();
    });
    geometries.forEach(g=>g.dispose());materials.forEach(m=>m.dispose());
    renderer?.dispose();scene?.clear();
  }
  try {
    scene = new THREE.Scene();
    const camera = new THREE.OrthographicCamera(-6, 6, 5, -5, .1, 100);
    camera.position.set(11, 14, 15);
    camera.lookAt(0, 1.15, 0);

    renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true, powerPreference: 'high-performance' });
    // Supersample even on a standard screen, with a pixel budget for smaller devices.
    function renderPixelRatio(width,height,dpr,compact,maxSize){
      const desired=Math.min(2.5,Math.max(1.75,(dpr||1)*1.25));
      const budget=compact?2000000:4200000;
      return Math.min(desired,Math.sqrt(budget/(width*height)),maxSize/Math.max(width,height));
    }
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.12;

    const world = new THREE.Group(); scene.add(world);
    const mat = (color, roughness=.82, metalness=0) => new THREE.MeshStandardMaterial({ color, roughness, metalness });
    const M = {
      cream:mat('#f2ead9'), ivory:mat('#fff8e9'), sage:mat('#78917d'), sageDark:mat('#526e5d'),
      mint:mat('#b6cbbd'), wood:mat('#94694d'), woodLight:mat('#c2986d'), clay:mat('#ca805b'),
      road:mat('#747e76'), roadLine:mat('#f8e8c7'), dark:mat('#303b37'), glass:mat('#78958e',.24,.05),
      tire:mat('#303a36'), leaf:mat('#557753'), leaf2:mat('#829c68'), yellow:mat('#e9c97e'),
      blue:mat('#477988'), metal:mat('#aeb8ad',.45,.45), white:mat('#f8f5eb'), skin:mat('#d9a27c')
    };
    cleanups.push(()=>Object.values(M).forEach(material=>material.dispose()));
    const group = (p,n,pos=[0,0,0]) => { const g=new THREE.Group(); g.name=n; g.position.set(...pos); p.add(g); return g; };
    function box(p,n,size,pos,material,opts={}) {
      const m=new THREE.Mesh(new THREE.BoxGeometry(...size),material.clone()); m.name=n; m.position.set(...pos); m.castShadow=opts.castShadow!==false; m.receiveShadow=opts.receiveShadow!==false;
      if(opts.rotation) m.rotation.set(...opts.rotation); p.add(m); return m;
    }
    function cyl(p,n,rt,rb,h,pos,material,seg=14,rot=[0,0,0]) {
      const m=new THREE.Mesh(new THREE.CylinderGeometry(rt,rb,h,seg),material.clone()); m.name=n; m.position.set(...pos); m.rotation.set(...rot); m.castShadow=true; m.receiveShadow=true; p.add(m); return m;
    }
    function ball(p,n,scale,pos,material,seg=12) {
      const m=new THREE.Mesh(new THREE.SphereGeometry(1,seg,Math.max(8,seg-2)),material.clone()); m.name=n; m.scale.set(...scale); m.position.set(...pos); m.castShadow=true; p.add(m); return m;
    }
    function platform(root, accent=M.woodLight) {
      box(root,'Floating miniature base',[8.3,.48,6.1],[0,0,0],M.cream);
      box(root,'Base top',[8.06,.10,5.86],[0,.29,0],M.ivory,{castShadow:false});
      box(root,'Front accent edge',[7.9,.09,.07],[0,.055,3.02],accent);
      box(root,'Left accent edge',[.07,.09,5.8],[-4.13,.055,0],accent);
    }
    function plant(p,n,x,z,s=1) {
      const q=group(p,n,[x,.36,z]);
      cyl(q,'Tapered terracotta planter',.20*s,.28*s,.38*s,[0,.19*s,0],M.clay,12);
      cyl(q,'Stem',.025*s,.035*s,.8*s,[0,.76*s,0],M.wood,8);
      for(let i=0;i<6;i++){const a=i*Math.PI/3;const l=ball(q,`Leaf ${i+1}`,[.12*s,.39*s,.052*s],[Math.cos(a)*.21*s,.86*s+(i%2)*.1*s,Math.sin(a)*.21*s],i%2?M.leaf:M.leaf2,10);l.rotation.z=-Math.cos(a)*.58;l.rotation.x=Math.sin(a)*.52;}
      return q;
    }

    function buildSpace(root) {
      platform(root,M.woodLight);
      const room=group(root,'Open-front rental room',[0,.36,-.2]);
      box(room,'Wood floor',[5.2,.12,3.8],[0,.05,-.85],M.woodLight);
      for(let i=0;i<12;i++) box(room,`Floorboard ${i+1}`,[5.05,.018,.025],[0,.12,-2.55+i*.31],M.wood,{castShadow:false});
      box(room,'Back wall',[5.2,3.45,.16],[0,1.82,-2.75],M.sage);
      box(room,'Left wall',[.16,3.45,3.8],[-2.52,1.82,-.85],M.sageDark);
      box(room,'Window frame',[1.55,1.68,.12],[-1.1,2.02,-2.64],M.ivory);
      box(room,'Window view',[1.34,1.47,.035],[-1.1,2.02,-2.55],M.glass,{castShadow:false});
      box(room,'Window crossbar',[1.37,.055,.06],[-1.1,2.02,-2.49],M.ivory,{castShadow:false});
      box(room,'Window sill',[1.75,.12,.22],[-1.1,1.16,-2.44],M.woodLight);
      box(room,'Sofa base',[2.18,.48,.74],[.15,.58,-.92],M.wood);
      box(room,'Sofa back',[2.18,.9,.28],[.15,1.04,-1.2],M.wood);
      box(room,'Sofa left arm',[.25,.64,.75],[-.94,.8,-.9],M.wood);
      box(room,'Sofa right arm',[.25,.64,.75],[1.24,.8,-.9],M.wood);
      box(room,'Seat cushion one',[.96,.14,.58],[-.36,.85,-.76],M.woodLight);
      box(room,'Seat cushion two',[.96,.14,.58],[.65,.85,-.76],M.woodLight);
      box(room,'Amber cushion',[.39,.4,.17],[-.35,1.13,-.69],M.clay,{rotation:[0,0,-.15]});
      box(room,'Green cushion',[.39,.4,.17],[.48,1.13,-.69],M.sageDark,{rotation:[0,0,.15]});
      box(room,'Coffee table',[1.75,.12,.76],[.15,.67,.55],M.woodLight);
      for(const [x,z] of [[-.55,.25],[.85,.25],[-.55,.86],[.85,.86]]) box(room,'Table leg',[.08,.58,.08],[x,.36,z],M.dark);
      box(room,'A book on the table',[.42,.055,.28],[-.3,.76,.48],M.blue);
      box(room,'Wall shelf',[1.45,.12,.28],[1.38,2.35,-2.55],M.ivory);
      box(room,'Shelf books',[.22,.52,.16],[1.03,2.67,-2.48],M.clay);
      box(room,'Shelf book two',[.19,.44,.16],[1.27,2.63,-2.48],M.blue);
      plant(room,'Indoor plant',-1.75,.87,.84).position.y=.11;
      // Wall-mounted television, facing the open side of the diorama.
      box(room,'Television frame',[.12,1.02,1.68],[-2.37,1.91,-.37],M.dark);
      box(room,'Television screen',[.025,.84,1.48],[-2.292,1.91,-.37],M.blue,{castShadow:false});
      ball(room,'Television sun',[.022,.085,.085],[-2.268,2.13,-.83],M.yellow);
      ball(room,'Television hill one',[.023,.18,.38],[-2.26,1.66,-.70],M.sageDark);
      ball(room,'Television hill two',[.023,.14,.34],[-2.255,1.65,-.10],M.leaf);
      box(room,'Television cloud one',[.024,.064,.23],[-2.26,2.02,-.65],M.ivory,{castShadow:false});
      box(room,'Television cloud two',[.024,.048,.18],[-2.255,2.17,-.12],M.ivory,{castShadow:false});
      box(room,'Television ticker',[.026,.04,1.25],[-2.25,1.55,-.37],M.mint,{castShadow:false});
      // lamp and floor-level luggage make this read as a bookable stay
      cyl(room,'Floor lamp stem',.035,.04,1.25,[2.05,.73,.2],M.metal,10);
      ball(room,'Floor lamp shade',[.24,.16,.24],[2.05,1.38,.2],M.ivory,12);
      box(room,'Travel bag',[.56,.42,.36],[2.02,.42,1.92],M.sageDark);
      box(room,'Travel bag handle',[.22,.06,.06],[2.02,.66,1.92],M.metal);
    }
    function buildVehicle(root) {
      platform(root,M.sage);
      // island road with dashed center line and green roadside verge
      box(root,'Roadway',[7.35,.10,2.65],[0,.37,.35],M.road);
      box(root,'Grass verge',[7.55,.12,1.15],[0,.36,-1.62],M.sage);
      box(root,'Road edge stripe',[7.32,.025,.045],[0,.43,-.95],M.white,{castShadow:false});
      box(root,'Road edge stripe front',[7.32,.025,.045],[0,.43,1.65],M.white,{castShadow:false});
      for(let i=0;i<6;i++) box(root,`Road dash ${i+1}`,[.58,.025,.07],[-2.85+i*1.14,.44,.35],M.roadLine,{castShadow:false});
      const van=group(root,'Filipino UV utility van',[.2,.43,.22]);
      box(van,'Van chassis',[2.65,.55,1.08],[0,.65,0],M.mint);
      box(van,'Van roof and cabin',[1.14,.83,1.0],[.52,1.31,0],M.ivory);
      box(van,'Windshield',[.055,.53,.8],[1.11,1.38,0],M.glass,{rotation:[0,0,-.17]});
      box(van,'Near side window',[.68,.42,.05],[.4,1.39,.52],M.glass);
      box(van,'Far side window',[.68,.42,.05],[.4,1.39,-.52],M.glass);
      box(van,'Sliding door',[.7,.42,.035],[-.54,.94,.55],M.cream);
      box(van,'Front bumper',[.18,.14,1.1],[1.37,.49,0],M.white);
      box(van,'Rear bumper',[.15,.13,1.08],[-1.38,.49,0],M.white);
      for(const z of [-.49,.49]) box(van,'Headlamp',[.12,.15,.06],[1.32,.72,z],M.yellow);
      for(const [x,z] of [[-.86,.57],[.88,.57],[-.86,-.57],[.88,-.57]]){
        cyl(van,'UV rubber wheel',.3,.3,.16,[x,.4,z],M.tire,18,[Math.PI/2,0,0]);
        cyl(van,'Wheel hub',.14,.14,.17,[x,.4,z*1.01],M.metal,14,[Math.PI/2,0,0]);
        for(let spoke=0;spoke<2;spoke++)box(van,'Wheel spoke',[.23,.026,.02],[x,.4,z+Math.sign(z)*.095],M.cream,{rotation:[0,0,spoke*Math.PI/2]});
      }
      van.traverse(object=>{if(object.isMesh)object.userData.activity='vehicle';});
      for(const [x,size] of [[-2.65,.88],[1.15,1.05]]){
        const tree=plant(root,'Roadside palm',x,-1.65,size);
        tree.traverse(object=>{if(object.isMesh){object.userData.sceneryX=x;object.userData.sceneryZ=-1.65;}});
      }
      box(root,'Rental pickup marker',[.32,.6,.18],[3.2,.72,-1.55],M.clay);
      box(root,'Marker sign',[.8,.42,.09],[3.2,1.13,-1.55],M.ivory);
      box(root,'Marker post',[.09,.6,.09],[3.2,.46,-1.55],M.wood);
      for(const name of ['Rental pickup marker','Marker sign','Marker post']){root.getObjectByName(name).userData.sceneryX=3.2;root.getObjectByName(name).userData.sceneryZ=-1.55;}
      // small traffic cone
      cyl(root,'Traffic cone base',.18,.18,.08,[2.7,.43,1.46],M.white,12);
      cyl(root,'Traffic cone',.02,.14,.42,[2.7,.67,1.46],M.clay,12);
      for(const name of ['Traffic cone base','Traffic cone']){root.getObjectByName(name).userData.sceneryX=2.7;root.getObjectByName(name).userData.sceneryZ=1.46;}
    }
    // Both arm segments retain a fixed length; the elbow bends toward the viewer.
    function elbowFor(shoulder,wrist,length=.52){
      const line=wrist.clone().sub(shoulder),distance=line.length();
      const direction=line.normalize();
      const bend=new THREE.Vector3(0,0,1).addScaledVector(direction,-direction.z).normalize();
      return shoulder.clone().lerp(wrist,.5).addScaledVector(bend,Math.sqrt(Math.max(0,length*length-distance*distance/4)));
    }
    function linkSegment(mesh,start,end,height=.55){
      const line=end.clone().sub(start);
      mesh.position.copy(start).lerp(end,.5);
      mesh.quaternion.setFromUnitVectors(new THREE.Vector3(0,1,0),line.clone().normalize());
      mesh.scale.y=line.length()/height;
    }
    function workerBody(room,prefix,x,z,material){
      const worker=group(room,prefix,[x,0,z]);
      box(worker,`${prefix} shirt`,[.49,.60,.34],[0,1.12,0],material);
      cyl(worker,`${prefix} neck`,.09,.10,.16,[0,1.48,0],M.skin,12);
      ball(worker,`${prefix} head`,[.21,.25,.205],[0,1.73,-.025],M.skin);
      ball(worker,`${prefix} hair`,[.218,.12,.21],[0,1.91,0],M.dark);
      ball(worker,`${prefix} nose`,[.065,.065,.075],[0,1.72,-.22],M.skin);
      for(const side of [-1,1]){
        ball(worker,`${prefix} ear ${side}`,[.045,.07,.045],[side*.21,1.73,0],M.skin);
        cyl(worker,`${prefix} leg ${side}`,.095,.105,.62,[side*.145,.52,0],M.dark,12);
        box(worker,`${prefix} shoe ${side}`,[.21,.13,.36],[side*.145,.145,-.06],M.dark);
      }
      return worker;
    }
    function workerArm(room,upperName,foreName,handName,shoulder,wrist,material){
      const a=new THREE.Vector3(...shoulder),b=new THREE.Vector3(...wrist),elbow=elbowFor(a,b);
      const upper=cyl(room,upperName,.09,.10,.55,[0,0,0],material,12);
      const fore=cyl(room,foreName,.065,.085,.55,[0,0,0],M.skin,12);
      linkSegment(upper,a,elbow);linkSegment(fore,elbow,b);
      ball(room,handName,[.085,.085,.085],wrist,M.skin);
    }
    function buildRakets(root) {
      platform(root,M.clay);
      const room=group(root,'Local services workshop',[0,.36,-.2]);
      box(room,'Workshop floor',[7.2,.11,4.3],[0,.03,-.35],M.cream);
      box(room,'Workshop back wall',[7.2,2.62,.14],[0,1.35,-2.47],M.ivory);
      box(room,'Pegboard',[2.05,1.2,.10],[-.42,1.68,-2.36],M.woodLight);
      for(let x=0;x<9;x++) for(let y=0;y<5;y++) ball(room,`Pegboard hole ${x}-${y}`,[.022,.022,.018],[-1.3+x*.22,1.22+y*.2,-2.29],M.wood,8);
      box(room,'Hammer handle',[.12,.72,.1],[-1.05,1.62,-2.19],M.wood);
      box(room,'Hammer head',[.42,.14,.15],[-1.05,2.02,-2.18],M.metal);
      box(room,'Wrench handle',[.13,.7,.1],[-.38,1.6,-2.19],M.blue);
      box(room,'Wrench jaw',[.3,.17,.1],[-.38,2.0,-2.19],M.metal);
      box(room,'Screwdriver shaft',[.07,.5,.06],[.27,1.71,-2.19],M.metal);
      box(room,'Screwdriver grip',[.17,.24,.14],[.27,1.34,-2.18],M.clay);
      box(room,'Workbench top',[2.2,.17,.96],[.25,.94,-.1],M.woodLight);
      for(const [x,z] of [[-.7,-.42],[1.2,-.42],[-.7,.23],[1.2,.23]]) box(room,'Workbench leg',[.11,.83,.11],[x,.48,z],M.wood);
      box(room,'Tool case',[.58,.4,.44],[-1.62,.61,.36],M.sageDark);
      box(room,'Tool case latch',[.2,.08,.05],[-1.62,.7,.59],M.metal);
      cyl(room,'Paint pail',.23,.25,.48,[2.85,.33,-.5],M.blue,14);
      cyl(room,'Paint pail rim',.25,.25,.07,[2.85,.59,-.5],M.metal,14);
      box(room,'Folded service cloth',[.38,.07,.30],[1.05,1.06,.05],M.sage);
      box(room,'Paint practice panel',[1.32,1.55,.05],[2.0,1.73,-2.36],M.cream);
      box(room,'Paint undercoat',[.62,.80,.018],[2.0,1.8,-2.32],M.ivory,{castShadow:false});
      box(room,'Fresh paint swatch',[.62,.025,.018],[2.0,1.4125,-2.295],M.mint,{castShadow:false});
      // Back of roller touches the visible paint face at z=-2.286.
      cyl(room,'Paint roller',.105,.105,.70,[2.0,1.425,-2.181],M.mint,16,[0,0,Math.PI/2]);
      box(room,'Roller neck',[.04,.24,.04],[2.35,1.305,-2.181],M.metal);
      box(room,'Roller bracket',[.35,.04,.04],[2.175,1.185,-2.181],M.metal);
      cyl(room,'Roller grip',.055,.06,.28,[2.0,1.025,-2.181],M.wood,12);
      const painter=workerBody(room,'Provider',1.50,-1.43,M.sageDark);
      box(painter,'Provider apron',[.36,.47,.05],[0,1.04,-.195],M.cream);
      workerArm(room,'Provider right arm','Painter forearm','Painter hand',[1.74,1.38,-1.43],[2.0,1.025,-2.181],M.sageDark);
      workerArm(room,'Provider left arm','Painter resting forearm','Painter resting hand',[1.26,1.38,-1.43],[1.15,.92,-1.58],M.sageDark);

      // A second provider drives a nail into a board, away from the supporting hand.
      workerBody(room,'Carpenter',.35,.79,M.clay);
      box(room,'Repair board',[1.12,.09,.52],[.27,1.07,-.12],M.wood);
      cyl(room,'Working nail',.025,.025,.10,[.10,1.17,-.24],M.metal,10);
      cyl(room,'Working nail cap',.055,.055,.025,[.10,1.225,-.24],M.metal,12);
      workerArm(room,'Carpenter upper arm','Carpenter forearm','Carpenter hand',[.10,1.38,.79],[.10,1.69,.10],M.clay);
      workerArm(room,'Carpenter support upper','Carpenter support forearm','Carpenter support hand',[.60,1.38,.79],[.69,1.16,.06],M.clay);
      const hammer=group(room,'Working hammer',[.10,1.69,.10]);
      cyl(hammer,'Working hammer handle',.038,.045,.42,[0,.17,0],M.wood,12);
      box(hammer,'Working hammer head',[.27,.13,.14],[0,.42,0],M.metal);
      hammer.rotation.x=-.55;

      // The third provider works at a standing computer desk facing the back wall.
      box(room,'Computer desk',[1.48,.12,.82],[-2.50,1.05,-1.32],M.woodLight);
      for(const x of [-3.1,-1.9])for(const z of [-1.60,-1.06])box(room,'Computer desk leg',[.09,.95,.09],[x,.56,z],M.wood);
      workerBody(room,'Technician',-2.50,-.36,M.blue);
      box(room,'Monitor foot',[.42,.05,.27],[-2.50,1.14,-1.5],M.dark);
      box(room,'Monitor stand',[.08,.24,.08],[-2.50,1.27,-1.55],M.dark);
      box(room,'Computer monitor',[1.0,.65,.09],[-2.50,1.65,-1.55],M.dark);
      box(room,'Computer screen',[.88,.53,.018],[-2.50,1.65,-1.49],M.blue,{castShadow:false});
      for(let i=0;i<4;i++)box(room,`Screen code ${i}`,[.31+(i%2)*.19,.025,.012],[-2.58,1.80-i*.095,-1.474],i%2?M.mint:M.ivory,{castShadow:false});
      box(room,'Screen cursor',[.035,.055,.014],[-2.22,1.51,-1.47],M.yellow,{castShadow:false});
      box(room,'Keyboard',[.65,.055,.23],[-2.52,1.14,-1.06],M.dark);
      for(let i=0;i<3;i++)box(room,`Keyboard row ${i}`,[.55,.008,.018],[-2.52,1.171,-1.13+i*.065],M.metal,{castShadow:false});
      ball(room,'Computer mouse',[.085,.035,.13],[-1.99,1.145,-1.08],M.ivory);
      workerArm(room,'Technician left upper','Technician left forearm','Technician left hand',[-2.74,1.38,-.36],[-2.70,1.22,-1.01],M.blue);
      workerArm(room,'Technician right upper','Technician right forearm','Technician right hand',[-2.26,1.38,-.36],[-2.30,1.22,-1.01],M.blue);
      plant(room,'Workshop plant',3.13,1.02,.62).position.y=.085;
      for(const [x,z] of [[-3.2,1.95],[-2.48,1.95],[2.48,1.95],[3.2,1.95]]) box(root,'Walkway stone',[.52,.08,.34],[x,.38,z],M.white);
    }
    // The builders above are pose definitions only. One persistent mesh pool is rendered.
    // Every slot owns one mesh, with three compatible vertex targets and world-space poses.
    const categoryKeys = ['spaces','vehicles','rakets'];
    const definitions = {};
    for (const [key,build] of Object.entries({spaces:buildSpace,vehicles:buildVehicle,rakets:buildRakets})) {
      const root = new THREE.Group(); build(root); root.updateMatrixWorld(true);
      const entries=[];
      root.traverse(o=>{
        if(!o.isMesh)return;
        const position=new THREE.Vector3(), rotation=new THREE.Quaternion(), scale=new THREE.Vector3();
        o.matrixWorld.decompose(position,rotation,scale);
        entries.push({name:o.name, kind:o.geometry.type, params:{...o.geometry.parameters},
          position,rotation,scale,color:o.material.color.clone(),roughness:o.material.roughness,
          metalness:o.material.metalness,sceneryX:o.userData.sceneryX,sceneryZ:o.userData.sceneryZ,activity:o.userData.activity||null,castShadow:o.castShadow,receiveShadow:o.receiveShadow});
      });
      definitions[key]=entries;
      root.traverse(o=>{if(o.isMesh){o.geometry.dispose();o.material.dispose();}});
    }

    // Explicit correspondences keep the story legible. Repeated names are consumed in order.
    const correspondence = [
      ['Floating miniature base','Floating miniature base','Floating miniature base'],
      ['Base top','Base top','Base top'],
      ['Front accent edge','Front accent edge','Front accent edge'],
      ['Left accent edge','Left accent edge','Left accent edge'],
      ['Wood floor','Roadway','Workshop floor'],
      ['Back wall','Grass verge','Workshop back wall'],
      ['Window frame','Van roof and cabin','Pegboard'],
      ['Window view','Windshield','Folded service cloth'],
      ['Sofa base','Van chassis','Tool case'],
      ['Coffee table','Sliding door','Workbench top'],
      ['Table leg','UV rubber wheel','Workbench leg'],
      ['Table leg','UV rubber wheel','Workbench leg'],
      ['Table leg','UV rubber wheel','Workbench leg'],
      ['Table leg','UV rubber wheel','Workbench leg'],
      ['Sofa left arm','Front bumper','Hammer handle'],
      ['Sofa right arm','Rear bumper','Screwdriver grip'],
      ['Amber cushion','Headlamp','Hammer head'],
      ['Green cushion','Headlamp','Wrench jaw'],
      ['Window crossbar','Near side window','Tool case latch'],
      ['Window sill','Far side window','Provider apron'],
      ['Left wall','Road edge stripe','Provider shirt'],
      ['Television frame','Marker sign','Paint practice panel'],
      ['Television screen','Road edge stripe front','Paint undercoat'],
      ['Television ticker','Road dash 1','Fresh paint swatch'],
      ['Television sun','Wheel hub','Paint roller'],
      ['Television cloud one','Wheel spoke','Roller neck'],
      ['Television cloud two','Wheel spoke','Roller bracket'],
      ['Television hill one','Wheel spoke','Roller grip'],
      ['Television hill two','Wheel spoke','Painter forearm'],
      ['Floor lamp stem','Marker post','Screwdriver shaft'],
      ['Sofa back','Rental pickup marker','Wrench handle'],
      ['Tapered terracotta planter','Tapered terracotta planter','Tapered terracotta planter'],
      ['Stem','Stem','Stem'],
      ...Array.from({length:6},(_,i)=>[`Leaf ${i+1}`,`Leaf ${i+1}`,`Leaf ${i+1}`])
    ];
    const pools = categoryKeys.map(k=>definitions[k].slice());
    const slots=[];
    for(const names of correspondence){
      const states=names.map((name,i)=>{
        const index=pools[i].findIndex(p=>p.name===name);
        if(index<0)throw new Error(`Missing morph correspondence: ${categoryKeys[i]} / ${name}`);
        return pools[i].splice(index,1)[0];
      });
      slots.push({id:names.join(' → '),states});
    }
    // Assign remaining ornaments by size and distance, with stable slot identities.
    const measure=p=>{
      const a=p.params;
      if(p.kind==='BoxGeometry')return a.width*a.height*a.depth*p.scale.x*p.scale.y*p.scale.z;
      if(p.kind==='CylinderGeometry')return Math.PI*Math.max(a.radiusTop,a.radiusBottom)**2*a.height;
      return 4*Math.PI/3*p.scale.x*p.scale.y*p.scale.z;
    };
    while(pools.some(p=>p.length)){
      const anchorPool=pools.reduce((best,p,i)=>p.length>pools[best].length?i:best,0);
      pools[anchorPool].sort((a,b)=>measure(b)-measure(a));
      const anchor=pools[anchorPool].shift(),states=[];
      for(let i=0;i<3;i++){
        if(i===anchorPool){states.push(anchor);continue;}
        if(!pools[i].length){states.push(null);continue;}
        let best=0,score=Infinity;
        pools[i].forEach((p,j)=>{
          const cost=Math.abs(Math.log((measure(p)+.00001)/(measure(anchor)+.00001)))*2
            +p.position.distanceToSquared(anchor.position)*.12+(p.kind===anchor.kind?0:.7);
          if(cost<score){score=cost;best=j;}
        });
        states.push(pools[i].splice(best,1)[0]);
      }
      const id=slots.length;
      // Surplus small details stow inside the opaque plinth; no alpha or mesh replacement.
      for(let i=0;i<3;i++)if(!states[i])states[i]={...anchor,name:'Detail stored inside platform',
        position:new THREE.Vector3((id%9-4)*.18,-.06,(Math.floor(id/9)%6-2.5)*.16),
        rotation:new THREE.Quaternion(),scale:new THREE.Vector3(.008,.008,.008),
        color:anchor.color.clone(),stowed:true};
      slots.push({id:states.map(s=>s.name).join(' → '),states});
    }

    // A common six-face grid can continuously bend from a block into a wheel or leaf.
    // All three targets have identical vertex indices; GPU morphing preserves mesh identity.
    const topology=new THREE.BoxGeometry(2,2,2,10,10,10);
    function shapeAttributes(pose){
      const base=topology.attributes.position,faceNormals=topology.attributes.normal;
      const positions=new Float32Array(base.count*3),normals=new Float32Array(base.count*3);
      const a=pose.params,v=new THREE.Vector3(),n=new THREE.Vector3();
      for(let i=0;i<base.count;i++){
        const x=base.getX(i),y=base.getY(i),z=base.getZ(i);
        if(pose.kind==='BoxGeometry'){
          v.set(x*a.width/2,y*a.height/2,z*a.depth/2);n.fromBufferAttribute(faceNormals,i);
        }else if(pose.kind==='CylinderGeometry'){
          const radial=Math.hypot(x,z), cap=Math.abs(faceNormals.getY(i))>.5;
          const disk=cap?Math.max(Math.abs(x),Math.abs(z)):1;
          const radius=THREE.MathUtils.lerp(a.radiusBottom,a.radiusTop,(y+1)/2);
          v.set(radial?x/radial*radius*disk:0,y*a.height/2,radial?z/radial*radius*disk:0);
          if(cap)n.set(0,faceNormals.getY(i),0);
          else n.set(x/radial,(a.radiusBottom-a.radiusTop)/a.height,z/radial).normalize();
        }else{
          n.set(x,y,z).normalize();v.copy(n).multiplyScalar(a.radius||1);
        }
        v.toArray(positions,i*3);n.toArray(normals,i*3);
      }
      return {position:new THREE.BufferAttribute(positions,3),normal:new THREE.BufferAttribute(normals,3)};
    }
    const sharedParts=slots.map((slot,index)=>{
      const geometry=topology.clone(),attributes=slot.states.map(shapeAttributes);
      geometry.setAttribute('position',attributes[0].position.clone());
      geometry.setAttribute('normal',attributes[0].normal.clone());
      geometry.morphAttributes.position=attributes.map(a=>a.position);
      geometry.morphAttributes.normal=attributes.map(a=>a.normal);
      geometry.morphTargetsRelative=false;
      const p=slot.states[0],material=new THREE.MeshStandardMaterial({color:p.color,roughness:p.roughness,metalness:p.metalness});
      const mesh=new THREE.Mesh(geometry,material);mesh.name=slot.id;mesh.frustumCulled=false;
      mesh.castShadow=slot.states.some(p=>p.castShadow);mesh.receiveShadow=true;
      mesh.position.copy(p.position);mesh.quaternion.copy(p.rotation);mesh.scale.copy(p.scale);
      mesh.morphTargetInfluences.splice(0,3,1,0,0);world.add(mesh);
      return {mesh,states:slot.states,index,anchor:index<4};
    });
    topology.dispose();

    const reduceMotion=window.matchMedia('(prefers-reduced-motion: reduce)');
    const pointerFine=window.matchMedia('(hover: hover) and (pointer: fine)');
    let activeMorph=null;
    const easeInOut=t=>{
      // cubic-bezier(.77,0,.175,1): solve x, then evaluate y.
      let lo=0,hi=1,u=t;
      for(let j=0;j<13;j++){const v=1-u,x=3*v*v*u*.77+3*v*u*u*.175+u*u*u;if(x<t)lo=u;else hi=u;u=(lo+hi)/2;}
      return 3*(1-u)*u*u+u*u*u;
    };
    function applyPose(part,index){
      const p=part.states[index],m=part.mesh;
      m.position.copy(p.position);m.quaternion.copy(p.rotation);m.scale.copy(p.scale);
      m.material.color.copy(p.color);m.material.roughness=p.roughness;m.material.metalness=p.metalness;
      m.morphTargetInfluences.forEach((_,j)=>m.morphTargetInfluences[j]=j===index?1:0);
    }
    function beginMorph(key,immediate=false){
      const target=categoryKeys.indexOf(key);
      if(immediate||reduceMotion.matches){sharedParts.forEach(p=>applyPose(p,target));activeMorph=null;return;}
      activeMorph={start:performance.now(),duration:1100,target,parts:sharedParts.map(part=>{
        const m=part.mesh,to=part.states[target];
        return {part,from:{position:m.position.clone(),rotation:m.quaternion.clone(),scale:m.scale.clone(),
          color:m.material.color.clone(),roughness:m.material.roughness,metalness:m.material.metalness,
          weights:m.morphTargetInfluences.slice()},to,
          delay:part.anchor?0:(part.index%4)*35,
          lift:part.anchor||to.stowed?0:Math.min(.48,m.position.distanceTo(to.position)*.11)};
      })};
    }
    function advanceMorph(now){
      if(!activeMorph)return;
      const a=activeMorph;
      for(const step of a.parts){
        const {part,from,to}=step,m=part.mesh;
        const t=THREE.MathUtils.clamp((now-a.start-step.delay)/a.duration,0,1),e=t===0?0:t===1?1:easeInOut(t);
        m.position.lerpVectors(from.position,to.position,e);
        m.position.y+=Math.sin(Math.PI*e)*step.lift;
        m.quaternion.slerpQuaternions(from.rotation,to.rotation,e);m.scale.lerpVectors(from.scale,to.scale,e);
        m.material.color.lerpColors(from.color,to.color,e);
        m.material.roughness=THREE.MathUtils.lerp(from.roughness,to.roughness,e);
        m.material.metalness=THREE.MathUtils.lerp(from.metalness,to.metalness,e);
        m.morphTargetInfluences.forEach((_,i)=>m.morphTargetInfluences[i]=THREE.MathUtils.lerp(from.weights[i],i===a.target?1:0,e));
      }
      if(now-a.start>=a.duration+105){sharedParts.forEach(p=>applyPose(p,a.target));activeMorph=null;}
    }
    listen(reduceMotion,'change',()=>{if(reduceMotion.matches&&activeMorph){const i=activeMorph.target;sharedParts.forEach(p=>applyPose(p,i));activeMorph=null;}});


    // Scene activity changes the same shared meshes used by the morph.
    // Morph snapshots include their current transforms, so activity never snaps them first.
    let activityCategory=null,activityTime=0;
    const activityGroups=categoryKeys.map((_,index)=>sharedParts.map(part=>({part,pose:part.states[index]})));
    const axisY=new THREE.Vector3(0,1,0),axisZ=new THREE.Vector3(0,0,1);
    const spinQuaternion=new THREE.Quaternion();
    const paintColors=[M.ivory,M.mint,M.clay,M.blue].map(material=>material.color.clone());
    function movingDash(baseX,time,width=.58){
      const edge=3.65,span=edge*2;
      const center=((baseX-time*.65+edge)%span+span)%span-edge;
      const left=Math.max(-edge,center-width/2),right=Math.min(edge,center+width/2);
      return {x:(left+right)/2,scale:(right-left)/width};
    }
    function paintCycle(time){
      const cycle=Math.floor(time/6),phase=(time%6)/6;
      const progress=Math.min(1,phase/.45);
      const coat=progress===0?0:progress===1?1:easeInOut(progress);
      const returnProgress=Math.min(1,Math.max(0,(phase-.45)/.45));
      const roller=phase<.45?coat:1-(returnProgress===1?1:returnProgress===0?0:easeInOut(returnProgress));
      return {coat,roller,oldColor:paintColors[cycle%paintColors.length],newColor:paintColors[(cycle+1)%paintColors.length]};
    }
    function animateActivity(dt,running,key){
      if(activeMorph){activityCategory=null;return;}
      if(activityCategory!==key){activityCategory=key;activityTime=0;}
      if(running)activityTime+=dt;
      const index=categoryKeys.indexOf(key),entries=activityGroups[index],time=activityTime;
      // Absolute poses prevent animation offsets from accumulating over repeated loops.
      for(const {part} of entries)applyPose(part,index);
      if(key==='vehicles'){
        const bob=Math.sin(time*4)*.012;
        for(const {part,pose} of entries){
          const mesh=part.mesh;
          if(pose.activity==='vehicle')mesh.position.y+=bob;
          if(pose.sceneryX!==undefined){
            const anchor=((pose.sceneryX-time*.65+3.75)%7.5+7.5)%7.5-3.75;
            // Compress the complete prop into the base at the ends before wrapping.
            const edge=THREE.MathUtils.smoothstep(Math.abs(anchor),2.9,3.55);
            const size=Math.max(.001,1-edge);
            mesh.position.x=anchor+(pose.position.x-pose.sceneryX)*size;
            mesh.position.y=.36-edge*.28+(pose.position.y-.36)*size;
            mesh.position.z=pose.sceneryZ+(pose.position.z-pose.sceneryZ)*size;
            mesh.scale.multiplyScalar(size);
          }
          if(pose.name.startsWith('Road dash')){
            const dash=movingDash(pose.position.x,time,pose.params.width);
            mesh.position.x=dash.x;mesh.scale.x=pose.scale.x*dash.scale;
          }
          if(pose.name==='UV rubber wheel'||pose.name==='Wheel hub'){
            spinQuaternion.setFromAxisAngle(axisY,-time*.65/.3);mesh.quaternion.multiply(spinQuaternion);
          }
          if(pose.name==='Wheel spoke'){
            spinQuaternion.setFromAxisAngle(axisZ,-time*.65/.3);mesh.quaternion.premultiply(spinQuaternion);
          }
        }
      }else if(key==='spaces'){
        for(const {part,pose} of entries){
          const mesh=part.mesh;
          if(pose.name.startsWith('Television cloud')){
            const low=-1.26,high=.12,span=high-low,width=pose.params.depth;
            const center=((pose.position.z+time*.075-low)%span+span)%span+low;
            const left=Math.max(low,center-width/2),right=Math.min(high,center+width/2);
            mesh.position.z=(left+right)/2;mesh.scale.z=pose.scale.z*(right-left)/width;
          }
          if(pose.name==='Television sun')mesh.position.y+=Math.sin(time*.65)*.025;
          if(pose.name.startsWith('Leaf ')){
            spinQuaternion.setFromAxisAngle(axisZ,(Math.sin(time*1.2+part.index*.6)-Math.sin(part.index*.6))*.035);mesh.quaternion.multiply(spinQuaternion);
          }
        }
      }else if(key==='rakets'){
        const find=name=>entries.find(e=>e.pose.name===name);
        const fresh=find('Fresh paint swatch'),undercoat=find('Paint undercoat'),roller=find('Paint roller');
        const cycle=paintCycle(time),height=undercoat.pose.params.height*undercoat.pose.scale.y;
        const bottom=undercoat.pose.position.y-height/2,paintHeight=.025+(height-.025)*cycle.coat;
        fresh.part.mesh.scale.y=paintHeight/fresh.pose.params.height;
        fresh.part.mesh.position.y=bottom+paintHeight/2;
        fresh.part.mesh.material.color.copy(cycle.newColor);
        undercoat.part.mesh.material.color.copy(cycle.oldColor);
        const rollerY=bottom+.025+(height-.025)*cycle.roller,offset=rollerY-roller.pose.position.y;
        for(const name of ['Paint roller','Roller neck','Roller bracket','Roller grip','Painter hand'])find(name).part.mesh.position.y+=offset;
        roller.part.mesh.material.color.copy(cycle.newColor);
        spinQuaternion.setFromAxisAngle(axisY,-cycle.roller*height/.105);roller.part.mesh.quaternion.multiply(spinQuaternion);
        const animateArm=(upper,fore,hand,shoulder)=>{
          const wrist=find(hand).part.mesh.position,elbow=elbowFor(shoulder,wrist);
          linkSegment(find(upper).part.mesh,shoulder,elbow);
          linkSegment(find(fore).part.mesh,elbow,wrist);
        };
        const shoulder=find('Provider shirt').pose.position.clone().add(new THREE.Vector3(.24,.26,0));
        animateArm('Provider right arm','Painter forearm','Painter hand',shoulder);

        // Slow lift, quick strike, brief contact; the head lands on the nail cap.
        const phase=(time%1.8)/1.8;
        const strike=-2.25,lift=-.55;
        const stroke=phase<.18?easeInOut(phase/.18):phase<.30?1:1-easeInOut((phase-.30)/.70);
        const angle=THREE.MathUtils.lerp(lift,strike,stroke);
        const hammerHand=find('Carpenter hand');
        hammerHand.part.mesh.position.y-=stroke*.09;
        const pivot=hammerHand.pose.position,livePivot=hammerHand.part.mesh.position;
        animateArm('Carpenter upper arm','Carpenter forearm','Carpenter hand',find('Carpenter shirt').pose.position.clone().add(new THREE.Vector3(-.25,.26,0)));
        const initial=new THREE.Quaternion().setFromAxisAngle(new THREE.Vector3(1,0,0),lift);
        const turn=new THREE.Quaternion().setFromAxisAngle(new THREE.Vector3(1,0,0),angle).multiply(initial.invert());
        for(const name of ['Working hammer handle','Working hammer head']){
          const {part,pose}=find(name);
          part.mesh.position.copy(pose.position).sub(pivot).applyQuaternion(turn).add(livePivot);
          part.mesh.quaternion.copy(turn).multiply(pose.rotation);
        }
        // Small alternating key presses stay attached to the forearms.
        for(const [side,offset] of [['left',0],['right',Math.PI]]){
          const hand=find(`Technician ${side} hand`);
          hand.part.mesh.position.y+=Math.sin(time*7+offset)*.018;
          const shoulder=find('Technician shirt').pose.position.clone().add(new THREE.Vector3(side==='left'?-.24:.24,.26,0));
          animateArm(`Technician ${side} upper`,`Technician ${side} forearm`,`Technician ${side} hand`,shoulder);
        }
        for(let i=0;i<4;i++){
          const code=find(`Screen code ${i}`),factor=.65+.35*(.5+.5*Math.sin(time*1.1+i));
          code.part.mesh.scale.x=code.pose.scale.x*factor;
          code.part.mesh.position.x=code.pose.position.x-code.pose.params.width*(1-factor)/2;
        }
        find('Screen cursor').part.mesh.position.x+=.05*Math.sin(time*2);

      }
    }


    const ground=new THREE.Mesh(new THREE.PlaneGeometry(100,100),new THREE.ShadowMaterial({color:'#587261',opacity:.12}));
    ground.rotation.x=-Math.PI/2; ground.position.y=-.46; ground.receiveShadow=true; scene.add(ground);
    scene.add(new THREE.HemisphereLight('#fffaf0','#71806b',2.05));
    const keyLight=new THREE.DirectionalLight('#fff2d7',3.1); keyLight.position.set(-6,12,9); keyLight.castShadow=true;
    const shadowSize=Math.min(window.innerWidth<700?2048:4096,renderer.capabilities.maxTextureSize);
    keyLight.shadow.mapSize.set(shadowSize,shadowSize); keyLight.shadow.camera.left=-9; keyLight.shadow.camera.right=9; keyLight.shadow.camera.top=9; keyLight.shadow.camera.bottom=-9; keyLight.shadow.bias=-.00015; keyLight.shadow.normalBias=.025; scene.add(keyLight);
    const fill=new THREE.DirectionalLight('#dcebe6',1.25); fill.position.set(9,8,-7); scene.add(fill);

    const pointer={x:0,y:0};
    const orbit={yaw:0,dragging:false,pointerId:null,lastX:0};
    let ambientPaused=false,ambientTime=0,lastFrame=null,stageVisible=true;
    function syncMotionButton(){onMotionChange({paused:ambientPaused,reduced:reduceMotion.matches});}
    listen(reduceMotion,'change',()=>{pointer.x=pointer.y=0;syncMotionButton();});
    syncMotionButton();

    listen(canvas,'pointerdown',e=>{
      if(e.button!==0||orbit.dragging)return;
      orbit.dragging=true;orbit.pointerId=e.pointerId;orbit.lastX=e.clientX;
      canvas.setPointerCapture(e.pointerId);canvas.classList.add('is-dragging');pointer.x=pointer.y=0;
    });
    listen(canvas,'pointermove',e=>{
      if(orbit.dragging){
        if(e.pointerId!==orbit.pointerId)return;
        orbit.yaw=THREE.MathUtils.clamp(orbit.yaw+(e.clientX-orbit.lastX)*.004,-.40,.40);
        orbit.lastX=e.clientX;return;
      }
      if(!pointerFine.matches)return;
      const r=canvas.getBoundingClientRect();
      pointer.x=THREE.MathUtils.clamp(((e.clientX-r.left)/r.width-.5)*2,-1,1);
      pointer.y=THREE.MathUtils.clamp(((e.clientY-r.top)/r.height-.5)*2,-1,1);
    },{passive:true});
    const releaseOrbit=e=>{
      if(e.pointerId!==orbit.pointerId)return;
      orbit.dragging=false;orbit.pointerId=null;canvas.classList.remove('is-dragging');
      if(canvas.hasPointerCapture(e.pointerId))canvas.releasePointerCapture(e.pointerId);
    };
    listen(canvas,'pointerup',releaseOrbit);
    listen(canvas,'pointercancel',releaseOrbit);
    listen(canvas,'lostpointercapture',releaseOrbit);
    listen(canvas,'pointerleave',()=>{pointer.x=pointer.y=0;});
    listen(canvas,'keydown',e=>{
      if(!['ArrowLeft','ArrowRight','Home'].includes(e.key))return;
      e.preventDefault();
      orbit.yaw=e.key==='Home'?0:THREE.MathUtils.clamp(orbit.yaw+(e.key==='ArrowRight'?.08:-.08),-.40,.40);
    });
    function ambientPose(seconds,reduced=false){
      if(reduced)return {y:0,yaw:0,roll:0};
      return {y:.16+.11*Math.sin(seconds*Math.PI*2/6.4),
        yaw:.19*Math.sin(seconds*Math.PI*2/32),roll:.008*Math.sin(seconds*Math.PI*2/12.8)};
    }
    function resize(){
      const r=canvas.getBoundingClientRect();if(!r.width||!r.height)return;
      renderer.setPixelRatio(renderPixelRatio(r.width,r.height,window.devicePixelRatio,window.innerWidth<700,renderer.capabilities.maxTextureSize));
      renderer.setSize(r.width,r.height,false);
      // Reserve room for the floating base and the full drag range.
      const aspect=r.width/r.height,h=Math.max(10.1,12.2/aspect);
      camera.left=-(h*aspect)/2;camera.right=(h*aspect)/2;camera.top=h/2;camera.bottom=-h/2;camera.updateProjectionMatrix();
    }
    const resizeObserver=new ResizeObserver(resize);resizeObserver.observe(canvas);cleanups.push(()=>resizeObserver.disconnect());
    listen(window,'resize',resize,{passive:true});resize();
    function syncRendering(){
      if(destroyed)return;
      if(suspended||document.hidden||!stageVisible){cancelAnimationFrame(frameId);frameId=0;lastFrame=null;}
      else if(!frameId)frameId=requestAnimationFrame(animate);
    }
    const intersectionObserver=new IntersectionObserver(entries=>{stageVisible=entries[0].isIntersecting;syncRendering();},{threshold:0});intersectionObserver.observe(canvas);cleanups.push(()=>intersectionObserver.disconnect());
    listen(document,'visibilitychange',syncRendering);
    function animate(now){
      if(destroyed)return;
      frameId=0;
      if(suspended||document.hidden||!stageVisible){lastFrame=null;return;}
      frameId=requestAnimationFrame(animate);
      const dt=lastFrame===null?0:Math.min(.05,(now-lastFrame)/1000);lastFrame=now;
      advanceMorph(now);
      animateActivity(dt,!ambientPaused&&!reduceMotion.matches&&!orbit.dragging,currentCategory);
      if(!ambientPaused&&!reduceMotion.matches&&!orbit.dragging)ambientTime+=dt;
      const pose=ambientPose(ambientTime,reduceMotion.matches),damping=1-Math.exp(-7*dt);
      const parallax=pointerFine.matches&&!reduceMotion.matches&&!ambientPaused;
      const targetYaw=orbit.yaw+pose.yaw+(parallax?pointer.x*.045:0);
      world.rotation.y=THREE.MathUtils.lerp(world.rotation.y,targetYaw,reduceMotion.matches?1:damping);
      world.rotation.x=THREE.MathUtils.lerp(world.rotation.x,parallax?pointer.y*.018:0,damping);
      world.rotation.z=THREE.MathUtils.lerp(world.rotation.z,pose.roll,damping);
      world.position.y=THREE.MathUtils.lerp(world.position.y,pose.y,damping);
      try { renderer.render(scene,camera); } catch(error) { dispose();onError(error); }
    }
    let currentCategory=resolveHeroCategory(category);
    beginMorph(currentCategory,true);
    listen(canvas,'webglcontextlost',event=>{event.preventDefault();dispose();onError(new Error('WebGL context lost'));});
    syncRendering();
    return {
      setCategory(value,{immediate=false}={}) {
        if(destroyed)return;
        const key=resolveHeroCategory(value);if(key===currentCategory)return;
        currentCategory=key;beginMorph(key,immediate);
      },
      togglePaused(){if(destroyed||reduceMotion.matches)return;ambientPaused=!ambientPaused;syncMotionButton();},
      setSuspended(value){suspended=Boolean(value);lastFrame=null;syncRendering();},
      resize,
      dispose,
    };

  
  } catch(error) { dispose();throw error; }
}
