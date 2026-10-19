export const compareKey = item => `${item.service}:${item.id}`;
export const formatPrice = value => new Intl.NumberFormat('en-PH', {style:'currency',currency:'PHP',maximumFractionDigits:0}).format(value);
export function distanceKm(lat,lng,targetLat,targetLng) {
  const values=[lat,lng,targetLat,targetLng];
  if(values.some(v=>v===''||v==null||!Number.isFinite(Number(v)))) return null;
  const [a,b,c,d]=values.map(Number);
  if(Math.abs(a)>90||Math.abs(c)>90||Math.abs(b)>180||Math.abs(d)>180)return null;
  const rad=v=>v*Math.PI/180;
  const h=Math.sin(rad(c-a)/2)**2+Math.cos(rad(a))*Math.cos(rad(c))*Math.sin(rad(d-b)/2)**2;
  return 6371*2*Math.asin(Math.sqrt(Math.min(1,Math.max(0,h))));
}
