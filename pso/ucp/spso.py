from random import randint
import random

swarm_size = 70
pf = 20
max_iter = 50
c1 = 2
c2 = 2
posisi = {}
partikel = {}
fitness_value = 200

rentangposisi = {
    "simple": {
        "lower": 5,
        "upper": 7.49
    },
    "average": {
        "lower": 7.5,
        "upper": 12.49
    },
    "complex": {
        "lower": 12.5,
        "upper": 15
    }
}

projectactual = {
    'simpleUC': 6,
    'averageUC': 10,
    'complexUC': 15,
    'uaw': 9,
    'tcf': 0.81,
    'ecf': 0.84,
    'actualEffort': 7970
}


def posisiacak():
    xsimple = random.uniform(rentangposisi['simple']['lower'], rentangposisi['simple']['upper'])
    xaverage = random.uniform(rentangposisi['average']['lower'], rentangposisi['average']['upper'])
    xcomplex = random.uniform(rentangposisi['complex']['lower'], rentangposisi['complex']['upper'])
    position = {
                'simple':xsimple,
                'average':xaverage,
                'complex':xcomplex
                }   
    return position

def size(xsimple, xaverage, xcomplex):
    ucsimple = xsimple * projectactual['simpleUC']
    ucaverage = xaverage * projectactual['averageUC']
    uccomplex = xcomplex * projectactual['complexUC']
    uucw = ucsimple + ucaverage + uccomplex
    uucp = projectactual['uaw'] + uucw
    return uucp * projectactual['tcf'] * projectactual['ecf']

def velocity(parameters):
    vsimple = (parameters['w'] * parameters['partikel']['vsimple']) + ((c1 * parameters['r1']) * (parameters['pbest']['simple'] - parameters['partikel']['simple'])) + ((c2 * parameters['r2']) * (parameters['gbest']['simple'] - parameters['partikel']['simple']))

    vaverage = (parameters['w'] * parameters['partikel']['vaverage']) + ((c1 * parameters['r1']) * (parameters['pbest']['average'] - parameters['partikel']['average'])) + ((c2 * parameters['r2']) * (parameters['gbest']['average'] - parameters['partikel']['average']))

    vcomplex = (parameters['w'] * parameters['partikel']['vcomplex']) + ((c1 * parameters['r1']) * (parameters['pbest']['complex'] - parameters['partikel']['complex'])) + ((c2 * parameters['r2']) * (parameters['gbest']['complex'] - parameters['partikel']['complex']))

    return {'vsimple': vsimple, 'vaverage': vaverage, 'vcomplex': vcomplex}

def inertia(iterasi):
    inertia_max = 0.9
    inertia_min = 0.4
    return inertia_min - (((inertia_max - inertia_min) * iterasi) / max_iter)

def exceedlimit(value, label):
    if label == 'simple' and value < rentangposisi['simple']['lower']:
        value = rentangposisi['simple']['lower']
    if label == 'simple' and value > rentangposisi['simple']['upper']:
        value = rentangposisi['simple']['upper']
    if label == 'average' and value < rentangposisi['average']['lower']:
        value = rentangposisi['average']['lower']
    if label == 'average' and value > rentangposisi['average']['upper']:
        value = rentangposisi['average']['upper']
    if label == 'complex' and value < rentangposisi['complex']['lower']:
        value = rentangposisi['complex']['lower']
    if label == 'complex' and value > rentangposisi['complex']['upper']:
        value = rentangposisi['complex']['upper']
    return value

def optimizer():
    partikel = []
    gbests = []
    pbests = []
    ret = []
    for iter in range(max_iter):
        print('iterasi', iter)
        w = inertia(iter)
        r1 = random.uniform(0, 1)
        r2 = random.uniform(0, 1)

        # initial population
        if iter == 0: 
            for i in range(swarm_size):
                xsimple = posisiacak()['simple']
                xaverage = posisiacak()['average']
                xcomplex = posisiacak()['complex']
                software_size = size(xsimple,xaverage,xcomplex)
                estimated_effort = software_size * pf
                ae = abs(projectactual['actualEffort'] - estimated_effort)
                vsimple = random.uniform(0, 1)
                vaverage = random.uniform(0, 1)
                vcomplex = random.uniform(0, 1)
                particles = {
                    'simple': xsimple,
                    'average': xaverage,
                    'complex': xcomplex,
                    'size': software_size,
                    'estimated': estimated_effort,
                    'ae': ae,
                    'vsimple':vsimple,
                    'vaverage':vaverage,
                    'vcomplex':vcomplex
                }
                partikel.append([])
                partikel[iter].append(particles)
                pbests.append([])
                pbests[iter].append(particles)
            min_ae = min(x['ae'] for x in pbests[iter])
            gbest = next(item for item in pbests[iter] if item['ae'] == min_ae)
            gbests.append([])
            gbests[iter].append(gbest)
            # print(gbests[iter])

        if iter > 0:
            for count,value in enumerate(partikel[iter-1]):
                parameters = {
                    'pbest': pbests[iter-1][count],
                    'partikel': value,
                    'gbest': gbests[iter-1][0],
                    'w':w,
                    'r1':r1,
                    'r2':r2
                }
                vel = velocity(parameters)
                xsimple = value['simple'] + vel['vsimple']
                xaverage = value['average'] + vel['vaverage']
                xcomplex = value['complex'] + vel['vcomplex']
                xsimple = exceedlimit(xsimple, 'simple')
                xaverage = exceedlimit(xaverage, 'average')
                xcomplex = exceedlimit(xcomplex, 'complex')
                software_size = size(xsimple,xaverage,xcomplex)
                estimated_effort = software_size * pf
                ae = abs(projectactual['actualEffort'] - estimated_effort)
                particles = {
                    'simple': xsimple,
                    'average': xaverage,
                    'complex': xcomplex,
                    'size': software_size,
                    'estimated': estimated_effort,
                    'ae': ae,
                    'vsimple':vel['vsimple'],
                    'vaverage':vel['vaverage'],
                    'vcomplex':vel['vcomplex']
                }
                partikel.append([])
                partikel[iter].append(particles)
                
                if pbests[iter-1][count]['ae'] > partikel[iter][count]['ae']:
                   pbests[iter].append(partikel[iter][count])
                else:
                    pbests[iter].append(partikel[iter-1][count])
                
            min_ae = min(x['ae'] for x in pbests[iter])
            gbest = next(item for item in pbests[iter] if item['ae'] == min_ae)
            gbests.append([])
            gbests[iter].append(gbest)
            #print(gbests[iter])
            
            if gbests[iter][0]['ae'] < fitness_value:
                return gbests[iter][0]
            ret.append(gbests[iter][0])
    return ret

results = optimizer()
min_ae = min(x['ae'] for x in results)
result = next(item for item in results if item['ae'] == min_ae)
print(result)