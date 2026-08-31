import React,{useEffect,useState} from 'react';
import {createRoot} from 'react-dom/client';
import axios from 'axios';
import './styles.css';

const api=axios.create({baseURL:import.meta.env.VITE_API_URL||'http://127.0.0.1:8000/api'});
const emptyC={name:'',email:'',document:'',phone:''};
const emptyS={customer_id:'',name:'',description:'',price:'',periodicity:'monthly',status:'active'};

function App(){
 const [tab,setTab]=useState('customers'),[customers,setCustomers]=useState([]),[subs,setSubs]=useState([]);
 const [cf,setCf]=useState(emptyC),[sf,setSf]=useState(emptyS),[editC,setEditC]=useState(null),[editS,setEditS]=useState(null);
 const [filter,setFilter]=useState(''),[detail,setDetail]=useState(null),[attempts,setAttempts]=useState([]);
 const [loading,setLoading]=useState(true),[error,setError]=useState('');

 const load=async()=>{setLoading(true);setError('');try{
  const [c,s]=await Promise.all([api.get('/customers'),api.get('/subscriptions',{params:filter?{status:filter}:{}})]);
  setCustomers(c.data);setSubs(s.data);
 }catch(e){setError(e.response?.data?.message||'No fue posible cargar los datos.')}finally{setLoading(false)}};
 useEffect(()=>{load()},[filter]);

 const saveC=async e=>{e.preventDefault();try{editC?await api.put(`/customers/${editC}`,cf):await api.post('/customers',cf);setCf(emptyC);setEditC(null);load()}catch(e){setError('No fue posible guardar el cliente.')}};
 const saveS=async e=>{e.preventDefault();try{const d={...sf,customer_id:Number(sf.customer_id),price:Number(sf.price)};editS?await api.put(`/subscriptions/${editS}`,d):await api.post('/subscriptions',d);setSf(emptyS);setEditS(null);load()}catch(e){setError('No fue posible guardar la suscripción.')}};
 const show=async id=>{try{const [s,a]=await Promise.all([api.get(`/subscriptions/${id}`),api.get(`/subscriptions/${id}/payment-attempts`)]);setDetail(s.data);setAttempts(a.data)}catch(e){setError('No fue posible cargar el detalle.')}};
 const run=async result=>{try{await api.post('/billing/run',{result});await load();if(detail)show(detail.id)}catch(e){setError('Error ejecutando el motor.')}};

 return <div className="app">
  <header><h1>Subscription Engine</h1><p>Clientes, suscripciones e intentos de cobro</p></header>
  <nav><button onClick={()=>setTab('customers')} className={tab==='customers'?'active':''}>Clientes</button><button onClick={()=>setTab('subscriptions')} className={tab==='subscriptions'?'active':''}>Suscripciones</button><button onClick={()=>run('random')}>Ejecutar cobro</button></nav>
  {error&&<div className="error">{error}</div>}
  {loading?<div className="state">Cargando...</div>:tab==='customers'?<section>
   <h2>{editC?'Editar cliente':'Nuevo cliente'}</h2>
   <form onSubmit={saveC} className="form">{['name','email','document','phone'].map(f=><input key={f} required placeholder={f} value={cf[f]} onChange={e=>setCf({...cf,[f]:e.target.value})}/>)}<button>Guardar</button></form>
   <h2>Clientes</h2>{!customers.length?<div className="state">No hay clientes registrados.</div>:<table><thead><tr><th>Nombre</th><th>Correo</th><th>Documento</th><th>Suscripciones</th><th>Acciones</th></tr></thead><tbody>{customers.map(c=><tr key={c.id}><td>{c.name}</td><td>{c.email}</td><td>{c.document}</td><td>{c.subscriptions?.length||0}</td><td><button onClick={()=>{setEditC(c.id);setCf({name:c.name,email:c.email,document:c.document,phone:c.phone})}}>Editar</button><button onClick={async()=>{await api.delete(`/customers/${c.id}`);load()}}>Eliminar</button></td></tr>)}</tbody></table>}
  </section>:<section>
   <div className="toolbar"><h2>{editS?'Editar suscripción':'Nueva suscripción'}</h2><select value={filter} onChange={e=>setFilter(e.target.value)}><option value="">Todos</option><option value="active">Activa</option><option value="paused">Pausada</option><option value="canceled">Cancelada</option></select></div>
   <form onSubmit={saveS} className="form"><select required value={sf.customer_id} onChange={e=>setSf({...sf,customer_id:e.target.value})}><option value="">Cliente</option>{customers.map(c=><option value={c.id} key={c.id}>{c.name}</option>)}</select><input required placeholder="Nombre" value={sf.name} onChange={e=>setSf({...sf,name:e.target.value})}/><input placeholder="Descripción" value={sf.description} onChange={e=>setSf({...sf,description:e.target.value})}/><input required type="number" min="0" step=".01" placeholder="Precio" value={sf.price} onChange={e=>setSf({...sf,price:e.target.value})}/><select value={sf.periodicity} onChange={e=>setSf({...sf,periodicity:e.target.value})}><option value="monthly">Mensual</option><option value="yearly">Anual</option></select><select value={sf.status} onChange={e=>setSf({...sf,status:e.target.value})}><option value="active">Activa</option><option value="paused">Pausada</option><option value="canceled">Cancelada</option></select><button>Guardar</button></form>
   {!subs.length?<div className="state">No hay suscripciones.</div>:<table><thead><tr><th>Plan</th><th>Cliente</th><th>Precio</th><th>Periodicidad</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>{subs.map(s=><tr key={s.id}><td>{s.name}</td><td>{s.customer?.name}</td><td>${s.price}</td><td>{s.periodicity}</td><td>{s.status}</td><td><button onClick={()=>show(s.id)}>Detalle</button><button onClick={async()=>{await api.delete(`/subscriptions/${s.id}`);load()}}>Eliminar</button></td></tr>)}</tbody></table>}
  </section>}
  {detail&&<section><h2>Detalle: {detail.name}</h2><p>Cliente: {detail.customer?.name}</p><p>Precio: ${detail.price}</p><p>Estado: {detail.status}</p><h3>Historial de intentos</h3>{!attempts.length?<div className="state">No hay intentos.</div>:<table><thead><tr><th>ID</th><th>Estado</th><th>Reintento</th><th>Próximo reintento</th></tr></thead><tbody>{attempts.map(a=><tr key={a.id}><td>{a.id}</td><td>{a.status}</td><td>{a.retry_count}</td><td>{a.next_retry_at||'-'}</td></tr>)}</tbody></table>}<button onClick={()=>setDetail(null)}>Cerrar</button></section>}
 </div>
}
createRoot(document.getElementById('root')).render(<App/>);